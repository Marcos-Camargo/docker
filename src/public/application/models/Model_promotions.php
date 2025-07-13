<?php
/*

Model de Acesso ao BD para Promoções

*/

require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";

use Microservices\v1\Integration\Price;

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 *
 * @property Model_products_marketplace $model_products_marketplace
 *
 * @property Price $ms_price
 */

class Model_promotions extends CI_Model
{

    private $createLog;

    private $all_makretplace = "'Todos','B2W','ML','VIA','CAR'";

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');

        $this->createLog = new CampaignsV2Logs();
    }


    /**
     * Excluir o preço do produto de promoção no microsserviço.
     *
     * @param   string          $marketplace    Apelico do marketplace.
     * @param   int             $product_id     Código do produto.
     * @param   int|string|null $variant        Ordem da variação.
     * @return  void
     */
    protected function deletePriceMicroservice(string $marketplace, int $product_id, $variant = null)
    {
        $variant = $variant === '' ? null : $variant;

        try {
            if ($this->ms_price->use_ms_price) {
                $this->ms_price->deletePromotionPrice($marketplace, $product_id, $variant);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }


    /**
     * Atualizar o preço do produto de promoção no microsserviço.
     *
     * @param   string          $marketplace    Apelico do marketplace.
     * @param   int|null        $product_id Código ID do produto.
     * @param   int|string|null $variant    Ordem da variação.
     * @param   array           $data       Dados de atualização.
     * @return  void
     */
    protected function updatePriceMicroservice(string $marketplace, int $product_id, $variant, array $data)
    {
        $variant = $variant === '' ? null : $variant;

        if (empty($data['price']) && empty($data['list_price'])) {
            return;
        }

        try {
            if ($this->ms_price->use_ms_price && (!empty($data['price']) || !empty($data['list_price']))) {
                $this->ms_price->updatePromotionPrice($product_id, $variant, $marketplace, $data['price'] ?? null, $data['list_price'] ?? null);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }

    public function getActivePromotions()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : " AND company_id = " . $this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM promotions active = ?" . $more;
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    /* get the brand data */
    public function getPromotionsData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM promotions WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        $sql = "SELECT * FROM promotions";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /* Le a promoção pelo ID do produto */
    public function getPromotionByProductId($product_id = null)
    {
        //$sql = "SELECT * FROM promotions WHERE product_id = ? AND active != 2";
        $sql = "SELECT * FROM promotions WHERE product_id = ? AND active = 1";
        $query = $this->db->query($sql, array($product_id));
        return $query->row_array();
    }


    public function getPromotionProductData($id)
    {

        $sql = "SELECT pr.*, p.name AS product, p.sku AS sku, p.price AS price_from, p.qty as stock, s.name as store ";
        $sql .= " FROM promotions pr";
        $sql .= " LEFT JOIN products p ON p.id=pr.product_id ";
        $sql .= " LEFT JOIN stores s ON s.id=pr.store_id ";
        $sql .= " WHERE pr.id = " . $id;
        $query = $this->db->query($sql);

        return $query->row_array();
    }

    public function getPromotionsViewData($offset, $procura, $orderby)
    {

        $filter = $this->session->userdata('promotionsfilter');
        if ($filter == "") {
            $filter = " AND (pr.active != 2 ) "; // default pegar somente os ativos
        }
        //$more = ($this->data['usercomp'] == 1) ? "": " AND pr.company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND pr.company_id = " . $this->data['usercomp'] : " AND pr.store_id = " . $this->data['userstore']);

        $condicao = substr(trim($more . $procura . $filter), 4);
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT pr.*, p.name AS product, p.sku AS sku, p.price AS price_from, p.qty as stock, s.name as store ";
        $sql .= "  FROM promotions pr";
        $sql .= " LEFT JOIN products p ON p.id=pr.product_id ";
        $sql .= " LEFT JOIN stores s ON s.id=pr.store_id ";
        $sql .= " WHERE " . $condicao . $orderby . " LIMIT 200 OFFSET " . $offset;;
        $query = $this->db->query($sql);
        // $this->session->set_flashdata('success',$sql);
        // var_dump($sql);
        return $query->result_array();
    }


    public function atualizaproductsdateupdate($groupId = null, $method = null)
    {

        if ($method === null) $method = __METHOD__;

        if ($groupId <> null) {

            $sql = "SELECT distinct P.product_id, P.price, P.active, PG.marketplace FROM promotions P INNER JOIN promotions_group PG ON PG.id = P.promotion_group_id WHERE P.promotion_group_id = '$groupId'";
            $query = $this->db->query($sql);
            $products = $query->result_array();

            if ($products) {
                foreach ($products as $product) {
                    $result = "";
                    $id = "";
                    $id = $product['product_id'];
                    $sql = "UPDATE products SET date_update = NOW() WHERE id = $id and date_update < DATE_ADD(NOW(), INTERVAL -60 MINUTE)";
                    $result = $this->db->query($sql);

                    $all_makretplace = array($product['marketplace']);

                    if ($all_makretplace[0] === 'Todos') {
                        $all_makretplace = explode(',', str_replace('Todos,','',str_replace("'", '', $all_makretplace)));
                    }

                    foreach ($all_makretplace as $mkt) {
                        if ($product['active'] == 1) {
                            $this->updatePriceMicroservice($mkt, $product['product_id'], null, array('price' => $product['price']));
                        } elseif ($product['active'] == 2) {
                            $this->deletePriceMicroservice($mkt, $product['product_id']);
                        }
                    }

                    $this->createLog->log(array('date_update' => date('Y-m-d H:i:s'),'product_id' => $id), $id, 'products', $method);
                    if (!$result) {
                        return false;
                    }
                }
                return true;
            } else {
                return true;
            }

        } else {

            $sql1 = "SELECT DISTINCT P.product_id FROM promotions P INNER JOIN promotions_group PG ON PG.id = P.promotion_group_id WHERE PG.data_atualizacao > DATE_ADD(NOW(), INTERVAL -20 MINUTE)";
            $query = $this->db->query($sql1);
            $products = $query->result_array();
            if ($products) {
                foreach ($products as $product) {
                    $result = "";
                    $id = "";
                    $id = $product['product_id'];
                    $sql = "UPDATE products SET date_update = NOW() WHERE id = $id";
                    $result = $this->db->query($sql);
                    $this->createLog->log(array('date_update' => date('Y-m-d H:i:s'),'product_id' => $id), $id, 'products', $method);
                    if (!$result) {
                        return false;
                    }
                }
                return true;
            } else {
                return true;
            }

        }


    }

    public function getPromotionsViewCount($procura = '')
    {
        $filter = $this->session->userdata('promotionsfilter');
        if ($filter == "") {
            $filter = " AND (pr.active != 2 ) "; // default pegar somente os ativos
        }
        $procura = $procura . $filter;
        // $more = ($this->data['usercomp'] == 1) ? "": " pr.company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? " pr.company_id " : (($this->data['userstore'] == 0) ? " pr.company_id = " . $this->data['usercomp'] : " pr.store_id = " . $this->data['userstore']);

        if ($procura == '') {
            if ($more != '') {
                $more = 'WHERE ' . $more;
            }
            $sql = "SELECT count(*) as qtd FROM promotions pr " . $more;
        } else {
            $sql = "SELECT count(*) as qtd FROM promotions pr ";
            $sql .= " LEFT JOIN products p ON p.id=pr.product_id";
            $sql .= " LEFT JOIN stores s ON s.id=pr.store_id ";
            $sql .= ' WHERE ' . $more . $procura;
        }
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getPromotionsOnCreationData()
    {
        //$more = ($this->data['usercomp'] == 1) ? "": " AND pr.company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND pr.company_id = " . $this->data['usercomp'] : " AND pr.store_id = " . $this->data['userstore']);

        $sql = "SELECT pr.*, p.name AS product, p.sku AS sku, p.price AS price_from, p.qty as stock, s.name as store";
        $sql .= " FROM promotions pr";
        $sql .= " LEFT JOIN products p ON p.id=pr.product_id ";
        $sql .= " LEFT JOIN stores s ON s.id=pr.store_id ";
        $sql .= " WHERE pr.active=3 " . $more;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('promotions', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('promotions', $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('promotions');
            return ($delete == true) ? true : false;
        }
    }

    public function aproveAll($id, $store_id)
    {
        if ($id) {
            $this->db->set('active', '4', FALSE);
            $this->db->where('company_id', $id);
            if ($store_id != 0) {
                $this->db->where('store_id', $store_id);
            }
            $update = $this->db->update('promotions', $data);
            return ($update == true) ? true : false;
        }
    }

    public function chanceActive($id, $active, $product_id = null,$method = null)
    {
        if ($method === null) $method = __METHOD__;

        if ($id) {
            $this->db->set('active', $active, FALSE);
            $this->db->where('id', $id);
            $update = $this->db->update('promotions');

        if($product_id != null){
            $sql = "UPDATE products SET date_update = NOW() WHERE id = $product_id";
            $result = $this->db->query($sql);
            $this->createLog->log(array('date_update' => date('Y-m-d H:i:s'),'product_id' => $product_id), $product_id, 'products', $method);

            $sql = "DELETE FROM promotions WHERE product_id = '$product_id' AND id = '$id'";
            $this->db->query($sql);
            $this->createLog->log(array('product_id' => $product_id,'id' => $id), $id, 'promotions', $method);
        }

            return ($update == true) ? true : false;
        }
    }

    public function activateAndDeactivate()
    {

        // coloco os que estavam planejados como ativo os que já chegaram no dia
        $sql = "UPDATE promotions SET active=1 WHERE active IN (0,4) AND NOW() > start_date";
        $query = $this->db->query($sql);

        // coloco os todoos como inativos quando termina o dia
        $sql = "UPDATE promotions_group SET ativo=2, data_atualizacao = now() WHERE ativo!=2 AND NOW() > data_fim ";
        $query = $this->db->query($sql);

        // coloco os todoos como inativos quando termina o dia
        $sql = "UPDATE promotions SET active=2 WHERE active!=2 AND NOW() > end_date ";
        $query = $this->db->query($sql);

        $this->desativarativarpromocao(null);

        // coloco os todoos como inativos quando termina o dia
        $sql = "UPDATE promotions SET active=2 WHERE promotion_group_id in (select distinct id from promotions_group where ativo = 0) and active != 0";
        $query = $this->db->query($sql);

        // coloco os todoos como inativos quando termina o dia
        //braun -> ignora o status 5
        // $sql = "UPDATE promotions SET active=1 WHERE promotion_group_id in (select distinct id from promotions_group where ativo = 1) and active != 0 and NOW() > start_date and NOW() < end_date";
        $sql = "UPDATE promotions SET active=1 WHERE promotion_group_id in (select distinct id from promotions_group where ativo = 1) and active != 0 and NOW() > start_date and NOW() < end_date and active != 5";
        $query = $this->db->query($sql);
        return $query;
    }

    public function updatePromotionByStock($product_id, $itemqty, $itemprice)
    {

        $sql = "SELECT * FROM promotions "; // ve se tem uma promoção por estoque para este produto ativo com este preço
        $sql .= " WHERE product_id = ? AND active = 1 AND type = 1 AND price = ?";
        $query = $this->db->query($sql, array($product_id, $itemprice));
        $promotion = $query->row_array();
        if ($promotion) { // existe então marco com mais um vendido
            $promotion['qty_used'] = (int)$promotion['qty_used'] + (int)$itemqty;
            if ($promotion['qty_used'] >= $promotion['qty']) {
                $promotion['active'] = 0; // Acabou a promoção
            }
            $this->update($promotion, $promotion['id']);
        }

        return;
    }

    public function getPriceProduct($product_id, $price_default, $mktplace = "Todos", $variant = null)
    {
        $this->load->model('model_campaigns_v2');

        /**
         * Se está participando de alguma campanha vigente, o valor a ser usado sempre será o que está na campanha
         */
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $productCampaignPrice = $this->model_campaigns_v2->getProductPriceInCampaigns($product_id, $mktplace, $variant);
        }
        else
        {
            $productCampaignPrice = $this->model_campaigns_v2->getProductPriceInCampaigns($product_id, $mktplace);
        }

        if ($productCampaignPrice){
            return $productCampaignPrice;
        }

        /*
        $sql = "SELECT c.*, cp.price as sale FROM campaigns c ";
        $sql.= " LEFT JOIN campaigns_products cp ON cp.campaigns_id=c.id ";
        $sql.= " WHERE (c.active = 1) AND cp.price != '' AND cp.product_id = ? LIMIT 1" ;
        $query = $this->db->query($sql, array($product_id));
        $campaign= $query->row_array();
        if ($campaign) { // existe então pego o preço da campanha
            return $campaign['sale'];
        }
        */

        if($mktplace == "Todos"){
            $filtro = $this->all_makretplace;
        } else {
            $filtro = "'Todos','$mktplace'";
        }

        $sql = "SELECT P.* FROM promotions P inner join promotions_group PG on PG.id = P.promotion_group_id"; // ve se tem uma promoção para este produto ativo

        $sql .= " WHERE P.product_id = ? AND P.active = 1 AND PG.tipo_promocao = 1 AND PG.ativo = 1 AND PG.marketplace in ($filtro) ";
        $sql .= " AND PG.data_fim > NOW()"; // se não inativou, mesmo assim não pego promoção vencida
        // $sql .= " WHERE P.product_id = ? AND P.active = 1 AND PG.percentual_promocao is null AND PG.ativo = 1 AND PG.marketplace in ($filtro) and end_date > now()";
        //$sql .= " order by P.price asc limit 1";
        $sql .= " ORDER BY P.promotion_group_id DESC limit 1";
        $query = $this->db->query($sql, array($product_id));
        $promotion = $query->row_array();
        if ($promotion) { // existe então pego o preço da promoção
            return $promotion['price'];
        }
        return $price_default;
        // nao existe, devolvo o preço original
    }

    public function verifyPromotionOfStore($id)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND pr.company_id = " . $this->data['usercomp'] : " AND pr.store_id = " . $this->data['userstore']);

        $sql = "SELECT pr.*, p.name AS product, p.sku AS sku, p.price AS price_from, p.qty as stock, s.name as store ";
        $sql .= " FROM promotions pr";
        $sql .= " LEFT JOIN products p ON p.id=pr.product_id ";
        $sql .= " LEFT JOIN stores s ON s.id=pr.store_id ";
        $sql .= " WHERE pr.id = " . $id . $more;
        $query = $this->db->query($sql);

        return $query->row_array();
    }

    public function getMyProductsPromotionsData($input = array(), $post = [])
    {
        //$more = ($this->data['usercomp'] == 1) ? "": " AND p.company_id = ".$this->data['usercomp'];
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $productsfilter = $this->session->userdata('productsfilter');

        $where = "";

        if (array_key_exists("slc_categoria_n1", $input)) {
            if ($input['slc_categoria_n1'] <> "") {
                $where .= " AND TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) = '" . $input['slc_categoria_n1'] . "'";
            }
        }

        if (array_key_exists("slc_categoria_n2", $input)) {
            if ($input['slc_categoria_n2'] <> "") {
                $where .= " AND TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) = '" . $input['slc_categoria_n2'] . "'";
            }
        }

        if (array_key_exists("slc_categoria_n3", $input)) {
            if ($input['slc_categoria_n3'] <> "") {
                $where .= " AND TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) = '" . $input['slc_categoria_n3'] . "'";
            }
        }

        $initialSql = "SELECT distinct p.*, c.name AS category, s.name AS store, ";
        $initialSql .= " TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) AS categoryN1, ";
        $initialSql .= " TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) AS categoryN2, ";
        $initialSql .= " TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) AS categoryN3 ";

        $sqlCount = " SELECT COUNT(*) as qtd";

        $sql = " FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= " WHERE p.status = 1 AND p.situacao = 2 AND p.is_kit = 0 AND p.qty > 0 "; //só os produtos que estão ativos. E que não estejam já em uma promoção, não sejam kit e que tenham estoque
        // $sql .= " AND p.id NOT IN (SELECT pr.product_id FROM promotions pr WHERE p.id = pr.product_id AND (pr.active = 1) UNION SELECT pr.product_id FROM promotions_temp pr WHERE pr.lote = '" . $input['hdnLote'] . "')";
        // retirada a regra de um produto não poder estar ativo em outras promoções conforme alinhado com o Guilherme dia 19/04/21
        $sql .= " AND p.id NOT IN (SELECT pr.product_id FROM promotions_temp pr WHERE pr.lote = '" . $input['hdnLote'] . "')";

        if ($input['search']) {
            $search = $input['search'];
            $where .= " and (p.id like '%$search%' or p.sku like '%$search%' or p.name like '%$search%')";
        }

        $sql .= $where;
        $sql .= $more . $productsfilter;

        $orderBy = '';
        if ($post['order']) {
            $column = $post['order'][0]['column']+1;

            $direction = $post['order'][0]['dir'];
            $orderBy = " ORDER BY $column $direction ";
        }

        $sqlLimit = 'LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];

        $query = $this->db->query($initialSql . $sql . $orderBy . $sqlLimit)->result_array();
        $count = $this->db->query($sqlCount . $sql)->result_array();
        return ['data' => $query, 'count' => $count[0]['qtd']];

    }

    public function checkproductacitveotherpromotion($input){

        $sql = "SELECT COUNT(*) AS qtd FROM promotions P
        INNER JOIN promotions_group PG ON PG.id = P.promotion_group_id
        WHERE product_id = ".$input['produto']." AND (P.active IN (1) OR (P.active IN (2) AND start_date > NOW()));";

        $query = $this->db->query($sql)->result_array();

        return $query[0]['qtd'];

    }

    public function desativaprodutospromocoes($input){

        $sql = "select P.id from promotions P 
                where promotion_group_id <> ".$input['id_promocao_group']." and product_id in (select product_id from promotions where promotion_group_id = ".$input['id_promocao_group'].") and (P.active IN (1) OR (P.active IN (2) AND start_date > NOW()))";

        $query = $this->db->query($sql)->result_array();

        if($query){

            foreach($query as $result){
                $id = $result['id'];
                $update = "update promotions P set active = 5 where P.id = $id";
                $this->db->query($update);
            }

            return true;

            /* $update = "update promotions P set active = 0
            where promotion_group_id <> ".$input['id_promocao_group']." and product_id in (select product_id from promotions where promotion_group_id = ".$input['id_promocao_group'].") and (P.active IN (1) OR (P.active IN (2) AND start_date > NOW()))"; */

        }else{
            return true;
        }

    }

    public function insertPromotionTemp($input)
    {

        $lote = $input['hdnLote'];
        $id = $input['produto'];

        $dataInicio = $input['txt_start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " " . $input['start_date_hour'] . ":00";

        $dataFim = $input['txt_end_date'];

        $dataFim = $input['txt_end_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " " . $input['end_date_hour'] . ":00";

        /*$sql = "INSERT INTO promotions_temp (`lote`,`product_id`,`active`,`type`,`qty`,`qty_used`,`price`,`start_date`,`end_date`,`store_id`,`company_id`)
                SELECT '$lote', id, 0, 0, qty, 0, price, '$dataInicio','$dataFim', store_id, company_id FROM products WHERE id = $id";

        $update1 =  $this->db->query($sql);*/

        $sql = "SELECT * FROM products WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        $row = $query->row_array();

        if ($row) {

            $data['lote'] = $lote;
            $data['product_id'] = $row['id'];
            $data['active'] = 0;
            $data['type'] = 0;
            $data['qty'] = $row['qty'];
            $data['qty_used'] = 0;
            $data['price'] = $row['price'];
            $data['start_date'] = $dataInicio;
            $data['end_date'] = $dataFim;
            $data['store_id'] = $row['store_id'];
            $data['company_id'] = $row['company_id'];

            $insert = $this->db->insert('promotions_temp', $data);
            $promo_id = $this->db->insert_id();

            return $promo_id;

        } else {
            return false;
        }

    }

    public function getPromotionsTemp($lote)
    {

        //$more = ($this->data['usercomp'] == 1) ? "": " AND p.company_id = ".$this->data['usercomp'];
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $productsfilter = $this->session->userdata('productsfilter');

        $sql = "SELECT DISTINCT p.*, c.name AS category, s.name AS store, DATE_FORMAT(pt.start_date, '%d/%m/%Y %H:%i') AS start_date, DATE_FORMAT(pt.end_date , '%d/%m/%Y %H:%i') AS end_date, pt.active as ativoTemp, pt.price as precoNovo, pt.qty as qtdPromo, pg.percentual_seller, case when pt.active <> 0 then round((1-(pt.price/p.price))*100,2) else 0 end as percentualDesconto FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= ' LEFT JOIN promotions_temp pt on p.id = pt.product_id ';
        $sql .= ' LEFT JOIN promotions_group pg on pg.lote = pt.lote ';
        $sql .= " WHERE p.status = 1 AND p.situacao = 2 AND p.is_kit = 0  "; //só os produtos que estão ativos. E que não estejam já em uma promoção e não sejam kit
        $sql .= " AND pt.lote = '$lote'";
        $sql .= $more . $productsfilter;

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function getPromotionsGroupData($tipoPromocao = null, $id = null)
    {

        $more = "";
        if ($tipoPromocao == "1") {
            $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND pg.company_id = " . $this->data['usercomp'] : " AND pg.store_id = " . $this->data['userstore']);
        }

        $where = "";

        if ($tipoPromocao <> null) {
            $where = " and tipo_promocao = $tipoPromocao ";
        }

        if ($id <> null) {
            $where = " and id = $id";
        }

        $sql = "SELECT id, lote, nome, descricao, marketplace, tipo_promocao, DATE_FORMAT(data_inicio , '%d/%m/%Y %H:%i') AS data_inicio, DATE_FORMAT(data_fim , '%d/%m/%Y %H:%i') AS data_fim, 
                DATE_FORMAT(data_criacao , '%d/%m/%Y %H:%i') AS data_criacao, 
                CASE WHEN ativo = 1 THEN 'Ativo' 
                    when ativo = 2 then 'Expirada' 
                    when ativo = 3 then 'Ativo - Aguardando o início da Promoção'
                    ELSE 'Desativada' END AS ativo , percentual_promocao , percentual_seller, categoria_n1, categoria_n2, categoria_n3, ativo AS ativo_id
                FROM promotions_group pg
                WHERE 1=1 $where $more
                order by CASE WHEN ativo = 1 THEN 'Ativo' 
                    when ativo = 2 then 'Expirada' 
                    when ativo = 3 then 'Ativo - Aguardando o início da Promoção'
                    ELSE 'Desativada' END, data_fim desc";

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function removeProductsFromPromotionTemp($id, $lote)
    {

        $sql = "delete from promotions_temp where product_id = '$id' and lote = '$lote'";
        return $this->db->query($sql);

    }

    public function AproveProductsFromPromotionTemp($inputs)
    {

        $campoPreco = "";
        $campoQtdPromo = "";

        $percentual = str_replace("%", "", $inputs['desconto']);

        if ($inputs['desconto'] <> "") {
            $campoPreco = ", price = round( (SELECT price FROM products WHERE id = '{$inputs['produto']}') - ((SELECT price FROM products WHERE id = '{$inputs['produto']}') * (" . $percentual . "/100)),2)  ";
        } else {
            $campoPreco = ", price = '" . $inputs['preco'] . "'";
        }

        if ($inputs['qtdPromo'] <> "") {
            $campoQtdPromo = ", qty = '" . $inputs['qtdPromo'] . "'";
        }

        $sql = "update promotions_temp set active = 1 $campoQtdPromo $campoPreco where product_id = '" . $inputs['produto'] . "' and lote = '" . $inputs['hdnLote'] . "' and active = 0";

        return $this->db->query($sql);

    }

    public function insertpromotiongroup($input)
    {

        $dataInicio = $input['txt_start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " " . $input['start_date_hour'] . ":00";

        $dataFim = $input['txt_end_date'];

        $dataFim = $input['txt_end_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " " . $input['end_date_hour'] . ":00";

        if (array_key_exists("typeAtivo", $input)) {
            $ativo = 1;
        } else {
            $ativo = 0;
        }

        if (array_key_exists("typepromo", $input)) {
            $typepromo = 2;
        } else {
            $typepromo = 1;
        }

        $data['lote'] = $input['hdnLote'];
        $data['nome'] = $input['txt_nome_promocao'];
        $data['descricao'] = $input['txt_desc_promocao'];
        $data['marketplace'] = $input['slc_marketplace'];
        $data['data_inicio'] = $dataInicio;
        $data['data_fim'] = $dataFim;
        $data['tipo_promocao'] = $typepromo;
        $data['ativo'] = $ativo;
        $data['store_id'] = $input['store_id'];
        $data['company_id'] = $input['company_id'];

        $insert = $this->db->insert('promotions_group', $data);
        $promo_id = $this->db->insert_id();

        if ($promo_id) {
            $teste = $this->salvavalorestemppromofinal($input, $promo_id);
            if($teste){
                $this->desativarativarpromocao($promo_id);
                $this->desativarativarpromocao();
                return $promo_id;
            }
        } else {
            return false;
        }

    }

    public function getFirstProductTemp($lote)
    {

        $sql = "select * from promotions_temp where lote = '$lote' order by id asc";
        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function salvavalorestemppromofinal($input, $promo_id)
    {
        $sql1 = "delete from promotions where promotion_group_id = '$promo_id'";
        $ret1 = $this->db->query($sql1);
        if ($ret1) {
            $sql = "INSERT INTO promotions (lote,product_id,active,`type`,qty,qty_used,price,start_date,end_date,store_id,company_id,date_create,date_update,promotion_group_id) 
                    SELECT lote,product_id,active,`type`,qty,qty_used,price,start_date,end_date,store_id,company_id,date_create,date_update,$promo_id
                    FROM promotions_temp WHERE lote = '" . $input['hdnLote'] . "'";
            $update1 = $this->db->query($sql);
            return $update1;
        }
    }

    public function desativarativarpromocao($id = null){

        $where = "";
        $desativar = "";
        $ativo = "";

        if ($id <> null) {
            $where = "where id = $id";
            //$desativar = "when ativo in (1,2,3) then 0  ";
            $desativar = " when ativo in (0) then 0 ";
            $ativo = " ativo = 0 and ";
        } else {
            $where = "where ativo <> case 
                                        $desativar
                                        when ativo in (0) then 0
                                        when $ativo data_inicio > now() and data_fim < now() then 1
                                        when $ativo data_inicio < now() and data_fim > now() then 1
                                        when $ativo data_inicio > now() and data_fim > now() then 3
                                    else 2 end";
        }

        $sql = "update promotions_group set ativo = 
                                                    case 
                                                        $desativar
                                                        when $ativo data_inicio > now() and data_fim < now() then 1
                                                        when $ativo data_inicio < now() and data_fim > now() then 1
                                                        when $ativo data_inicio > now() and data_fim > now() then 3
                                                    else 2 end, data_atualizacao = now() $where";

        $teste = $this->db->query($sql);

        if ($teste) {
            // coloco os todoos como inativos quando termina o dia
            $sql = "UPDATE promotions SET active=2 WHERE promotion_group_id in (select distinct id from promotions_group where ativo = 0) and active != 0";
            $query = $this->db->query($sql);
            if ($query) {
                // coloco os todoos como inativos quando termina o dia
                $sql = "UPDATE promotions SET active=1 WHERE promotion_group_id in (select distinct id from promotions_group where ativo = 1) and active != 0";

                $query = $this->db->query($sql);

                if ($query) {
                    return $this->atualizaproductsdateupdate($id,__METHOD__);
                } else {
                    return false;
                }


            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    public function getPromotionsDataToTemp($lote, $idGroup)
    {

        $sql1 = "delete from promotions_temp where lote = '$lote'";
        $ret1 = $this->db->query($sql1);
        if ($ret1) {

            $sql = "INSERT INTO promotions_temp (lote,product_id,active,`type`,qty,qty_used,price,start_date,end_date,store_id,company_id,date_create,date_update,promotion_group_id)
                    SELECT lote,product_id,active,`type`,qty,qty_used,price,start_date,end_date,store_id,company_id,date_create,date_update,$idGroup
                    FROM promotions WHERE lote = '$lote' and promotion_group_id = $idGroup";

            return $this->db->query($sql);

        }
    }

    public function editpromotiongroup($input)
    {

        $dataInicio = $input['txt_start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " " . $input['start_date_hour'] . ":00";

        $dataFim = $input['txt_end_date'];

        $dataFim = $input['txt_end_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " " . $input['end_date_hour'] . ":00";

        if (array_key_exists("typeAtivo", $input)) {
            $ativo = 1;
        } else {
            $ativo = 0;
        }

        if (array_key_exists("typepromo", $input)) {
            $typepromo = 2;
        } else {
            $typepromo = 1;
        }

        $data['lote'] = $input['hdnLote'];
        $data['nome'] = $input['txt_nome_promocao'];
        $data['descricao'] = $input['txt_desc_promocao'];
        $data['marketplace'] = $input['slc_marketplace'];
        $data['data_inicio'] = $dataInicio;
        $data['data_fim'] = $dataFim;
        $data['tipo_promocao'] = $typepromo;
        $data['ativo'] = $ativo;

        $this->db->where('id', $input['hdnEdit']);
        $edit = $this->db->update('promotions_group', $data);

        if ($edit) {
            $teste = $this->salvavalorestemppromofinal($input, $input['hdnEdit']);
            if($teste){
                $this->desativarativarpromocao($input['hdnEdit']);
                $this->desativarativarpromocao();
                return $input['hdnEdit'];
            }
        } else {
            return false;
        }

    }

    public function getCategories($nivel = 4)
    {

        $campos = "";
        $order = "";
        $where = "";

        if($nivel == "1"){
            $campos = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,1,LOCATE(\"/\",c.name)-1)) else TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) end AS categoryN1 ";
            $order = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,1,LOCATE(\"/\",c.name)-1)) else TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) end";
            $where = " and ".$order." <> ''";
        }elseif($nivel == "2"){
            $campos = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name)+1,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)-1 - LOCATE(\"/\",c.name))) else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) end AS categoryN2 ";
            $order = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name)+1,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)-1 - LOCATE(\"/\",c.name))) else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) end ";
            $where = " and ".$order." <> ''";
        }elseif($nivel == "3"){
            $campos = " case when locate(\">\",name) = 0 then CASE WHEN LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1) = '' THEN '' ELSE TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)+1,255 )) END else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) end AS categoryN3 ";
            $order = " case when locate(\">\",name) = 0 then CASE WHEN LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1) = '' THEN '' ELSE TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)+1,255 )) END else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) end";
            $where = " and ".$order." <> ''";
        }else{
            $campos = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,1,LOCATE(\"/\",c.name)-1)) else TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) end AS categoryN1, ";
            $campos .= " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name)+1,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)-1 - LOCATE(\"/\",c.name))) else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) end AS categoryN2, ";
            $campos .= " case when locate(\">\",name) = 0 then CASE WHEN LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1) = '' THEN '' ELSE TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)+1,255 )) END else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) end AS categoryN3 ";

            $order = " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,1,LOCATE(\"/\",c.name)-1)) else TRIM(SUBSTRING(c.name,1,LOCATE(\">\",c.name)-1)) end,";
            $order .= " case when locate(\">\",name) = 0 then TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name)+1,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)-1 - LOCATE(\"/\",c.name))) else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name)+1,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 - LOCATE(\">\",c.name))) end, ";
            $order .= " case when locate(\">\",name) = 0 then CASE WHEN LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1) = '' THEN '' ELSE TRIM(SUBSTRING(c.name,LOCATE(\"/\",c.name,LOCATE(\"/\",c.name)+1)+1,255 )) END else TRIM(SUBSTRING(c.name,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)+2,LOCATE(\">\",c.name,LOCATE(\">\",c.name)+1)-1 )) end";

        }

        $sql = "select distinct $campos from categories c where 1=1 $where order by $order";

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function cadastrarPromocaoFromCampanha($input)
    {

        $dataInicio = $input['start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " 00:00:00";

        $dataFim = $input['end_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " 23:59:59";

        $ativo = 1;
        $typepromo = 2;

        $data['lote'] = date('YmdHis') . rand(1, 1000000);
        $data['nome'] = $input['name'];
        $data['descricao'] = $input['description'];
        $data['marketplace'] = $input['slc_marketplace'];
        $data['data_inicio'] = $dataInicio;
        $data['data_fim'] = $dataFim;
        $data['tipo_promocao'] = $typepromo;
        $data['ativo'] = $ativo;

        $data['categoria_n1'] = $input['slc_categoria_n1'];
        $data['categoria_n2'] = $input['slc_categoria_n2'];
        $data['categoria_n3'] = $input['slc_categoria_n3'];

        $data['percentual_promocao'] = $input['txt_percent_promo'];
        $data['percentual_seller'] = $input['txt_percent_seller'];

        $insert = $this->db->insert('promotions_group', $data);
        return $this->db->insert_id();

    }

    public function addskumassivo($input)
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $lote = $input['hdnLote'];

        $dataInicio = $input['txt_start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " " . $input['start_date_hour'] . ":00";

        $dataFim = $input['txt_end_date'];

        $dataFim = $input['txt_end_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " " . $input['end_date_hour'] . ":00";

        $sku = str_replace(";", "','", $input['SKU']);

        $sql = "INSERT INTO promotions_temp (`lote`,`product_id`,`active`,`type`,`qty`,`qty_used`,`price`,`start_date`,`end_date`,`store_id`,`company_id`)
                SELECT '$lote', id, 0, 0, qty, 0, price, '$dataInicio','$dataFim', store_id, company_id FROM products WHERE sku in ('$sku') $more";

        $update1 = $this->db->query($sql);

        return $update1;

    }

    public function setPromotionByLote(array $input): bool
    {
        $formatInicio = explode("/", $input['txt_start_date']);
        $dataInicio = $formatInicio[2] . "-" . $formatInicio[1] . "-" . $formatInicio[0] . " " . $input['start_date_hour'] . ":00";

        $formatFim = explode("/", $input['txt_end_date']);
        $dataFim = $formatFim[2] . "-" . $formatFim[1] . "-" . $formatFim[0] . " " . $input['end_date_hour'] . ":00";

        if (array_key_exists("typeAtivo", $input)) {
            $ativo = 1;
        } else {
            $ativo = 0;
        }

        /* $data = array(
            'start_date' => $dataInicio,
            'end_date'   => $dataFim,
            'active'     => $ativo
        ); */

        $data = array(
            'start_date' => $dataInicio,
            'end_date'   => $dataFim
        );

        $this->db->where('lote', $input['hdnLote']);
        return (bool)$this->db->update('promotions', $data);

    }

    public function getProductPromotionByLote($lote)
    {
        $sql = "SELECT * FROM promotions WHERE lote = ? ORDER BY id ASC";
        $query = $this->db->query($sql, array($lote));
        return $query->result_array();

    }

    /**
     * Recupera o se o produto estava em produção quando o pedido foi realizado e seu preço de antes de depois da promoção.
     *
     * @param   int         $product    Código do produto (orders_item.product_id)
     * @param   string      $variant    Código da ordem da variação (orders_item.variant)
     * @param   string      $created    Data da criação do pedido (orders.date_time)
     * @param   string      $int_to     Marketplace (orders.origin)
     * @return  array|null              [promotion_price , product_price]
     */
    public function getProductPromotionByProductCreated(int $product, string $variant, string $created, string $int_to): ?array
    {
        $this->load->model('model_products_marketplace');
        $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($int_to, $product, $variant);

        $pricePrd = false;
        // existe preço para marketplace
        if ($dataMkt) {
            $pricePrd   = $dataMkt['same_price'] == 1 ? false : ($dataMkt['price'] === '' ? false : $dataMkt['price']);
        }

        $promotion = $this->db->select('promotions.price as promotion_price, products.price as product_price')
                        ->from('promotions')
                        ->join('products', 'products.id = promotions.product_id')
                        ->where('start_date <=', $created)
                        ->where('end_date >', $created)
                        ->where('product_id', $product)
                        ->get()
                        ->row_array();

        if (!$promotion) {
            return null;
        }

        $product_price = $promotion['product_price'];

        if ($pricePrd) {
            $product_price = $pricePrd;
        }

        return array(
            'promotion_price'   => $promotion['promotion_price'],
            'product_price'     => $product_price
        );
    }

    public function getPromotionByProductAndMarketplace($product_id, $marketplace)
    {
        if($marketplace == "Todos"){
            $filtro = $this->all_makretplace;
        } else {
            $filtro = "'Todos','$marketplace'";
        }

        $sql = "SELECT P.* FROM promotions P inner join promotions_group PG on PG.id = P.promotion_group_id"; // ve se tem uma promoção para este produto ativo

        $sql .= " WHERE P.product_id = ? AND P.active = 1 AND PG.tipo_promocao = 1 AND PG.ativo = 1 AND PG.marketplace in ($filtro) ";
        $sql .= " AND PG.data_fim > NOW()"; // se não inativou, mesmo assim não pego promoção vencida
        // $sql .= " WHERE P.product_id = ? AND P.active = 1 AND PG.percentual_promocao is null AND PG.ativo = 1 AND PG.marketplace in ($filtro) and end_date > now()";
        //$sql .= " order by P.price asc limit 1";
        $sql .= " ORDER BY P.promotion_group_id DESC limit 1";
        $query = $this->db->query($sql, array($product_id));
        $promotion = $query->row_array();
        if ($promotion) { // existe então pego o preço da promoção
            return $promotion['price'];
        }
        return null;
    }
}