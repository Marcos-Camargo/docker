<?php
/*

Model de Acesso ao BD para Campanhas

*/

class Model_campaigns extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function cadastrarCampanha($input)
    {

        $dataInicio = $input['start_date'];
        $formatInicio = explode("/", $dataInicio);
        $dataInicio = $formatInicio[2]."-".$formatInicio[1]."-".$formatInicio[0]." 00:00:00";

        $dataFim = $input['start_date'];
        $formatFim = explode("/", $dataFim);
        $dataFim = $formatFim[2]."-".$formatFim[1]."-".$formatFim[0]." 23:59:59";

        $data['campanha'] = $input['name'];
        $data['descricao'] = $input['description'];
        $data['marketplace'] = $input['slc_marketplace'];
        $data['data_inicio_campanha'] = $dataInicio;
        $data['data_fim_campanha'] = $dataFim;
        $data['tipo_campanha'] = $input['scl_tipo_campanha'];
        $data['taxa_reduzida_marketplace'] = $input['txt_taxa_mktplace_nova'];
        $data['taxa_reduzida_seller'] = $input['txt_taxa_seller_nova'];
        $data['tipo_pagamento'] = $input['scl_tipo_pagamento'];
        $data['total_percent_promocao'] = $input['txt_percent_promo'];
        $data['total_percent_seller'] = $input['txt_percent_seller'];
        $data['lote'] = $input['hdnLote'];

        $data['categoria_n1'] = $input['slc_categoria_n1'];
        $data['categoria_n2'] = $input['slc_categoria_n2'];
        $data['categoria_n3'] = $input['slc_categoria_n3'];

        $insert = $this->db->insert('campanha', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function insertprodutotemp($input)
    {

        $where = "";

        $tela = str_replace(";", "','", $input['txt_sku']);

        if ($input['buscaAuto'] == "sku") {
            $where = " AND sku in ('$tela')";
        }

        if ($input['buscaAuto'] == "id") {
            $where = " AND id in ('$tela')";
        }

        if ($input['buscaAuto'] == "nome") {
            $where = " AND `name` in ('$tela')";
        }

        if ($input['buscaAuto'] == "categoria") {
            $where = " AND `name` in ('$tela')";
        }

        $sql = "INSERT INTO campanha_sku_temp (lote,campanha_id,sku, nome,product_id,ativo) 
                SELECT '".$input['hdnLote']."' AS lote, 0 AS campanha_id, sku, `name`, id, 1 AS ativo FROM products WHERE 1=1 $where";

        $update1 = $this->db->query($sql);

        return $update1;

    }

    public function deleteprodutotemp($input)
    {

        $sql = "delete from campanha_sku_temp where lote = '".$input['hdnLote']."' and product_id = '".$input['produto']."'";
        return $this->db->query($sql);

    }

    public function getprodutotemp($input)
    {

        $sql = "select * from campanha_sku_temp where lote = '".$input['hdnLote']."'";

        $query = $this->db->query($sql);
        return $query->result_array();


    }

    public function cadastraSKUs($input, $idCampanha)
    {

        $sql = "delete from campanha_sku where lote = '".$input['hdnLote']."'";
        $retDelete = $this->db->query($sql);

        if ($retDelete) {

            $sql = "INSERT INTO campanha_sku (lote,campanha_id,sku, nome,product_id,ativo)
                    SELECT lote, $idCampanha, sku, nome, product_id, ativo FROM campanha_sku_temp WHERE lote = '".$input['hdnLote']."'";

            $update1 = $this->db->query($sql);

            return $update1;


        } else {
            return false;
        }

    }

    public function cadastraSKUsTemp($hdnLote)
    {

        $sql = "delete from campanha_sku_temp where lote = '$hdnLote'";
        $retDelete = $this->db->query($sql);

        if ($retDelete) {

            $sql = "INSERT INTO campanha_sku_temp (lote,campanha_id,sku, nome,product_id,ativo)
                    SELECT lote, campanha_id, sku, nome, product_id, ativo FROM campanha_sku WHERE lote = '$hdnLote'";

            $update1 = $this->db->query($sql);

            return $update1;


        } else {
            return false;
        }

    }

    public function editaCampanha($input)
    {

        $dataInicio = $input['start_date'];

        if (strpos($dataInicio, "/") === false) {
            $formatInicio = explode("-", $dataInicio);
            $dataInicio = $formatInicio[0]."-".$formatInicio[1]."-".$formatInicio[2]." 00:00:00";
        } else {
            $formatInicio = explode("/", $dataInicio);
            $dataInicio = $formatInicio[2]."-".$formatInicio[1]."-".$formatInicio[0]." 00:00:00";
        }

        $dataFim = $input['end_date'];

        if (strpos($dataFim, "/") === false) {
            $formatFim = explode("-", $dataFim);
            $dataFim = $formatFim[0]."-".$formatFim[1]."-".$formatFim[2]." 23:59:59";
        } else {
            $formatFim = explode("/", $dataFim);
            $dataFim = $formatFim[2]."-".$formatFim[1]."-".$formatFim[0]." 23:59:59";
        }

        $data['campanha'] = $input['name'];
        $data['descricao'] = $input['description'];
        $data['marketplace'] = $input['slc_marketplace'];
        $data['data_inicio_campanha'] = $dataInicio;
        $data['data_fim_campanha'] = $dataFim;
        $data['tipo_campanha'] = $input['scl_tipo_campanha'];
        $data['taxa_reduzida_marketplace'] = $input['txt_taxa_mktplace_nova'];
        $data['taxa_reduzida_seller'] = $input['txt_taxa_seller_nova'];
        $data['tipo_pagamento'] = $input['scl_tipo_pagamento'];
        $data['total_percent_promocao'] = $input['txt_percent_promo'];
        $data['total_percent_seller'] = $input['txt_percent_seller'];

        $data['categoria_n1'] = $input['slc_categoria_n1'];
        $data['categoria_n2'] = $input['slc_categoria_n2'];
        $data['categoria_n3'] = $input['slc_categoria_n3'];

        $this->db->where('lote', $input['hdnLote']);
        return $this->db->update('campanha', $data);

    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('campaigns', $data);
            return ($update == true) ? $id : false;
        }
    }



    /********************************************************/

    /* get the brand data */

    public function getCampanhasData($id = null)
    {

        $where = "";

        if ($id <> "") {
            $where = "where id = $id";
        }


        $sql = "select * from campanha $where order by id";

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function getCampaignsData($id = null)
    {
        if ($id) {
            $sql = "SELECT c.*, s.descloja AS marketplace FROM campaigns c ";
            $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to WHERE c.id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        $sql = "SELECT c.*, s.descloja AS marketplace FROM campaigns c LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCampaignsViewData($offset = 0, $procura, $orderby)
    {

        $filter = $this->session->userdata('campaignsfilter');
        if ($filter == "") {
            $filter = " AND (c.active != 2 ) "; // default pegar somente os ativos
        }
        $condicao = substr(trim($procura.$filter), 4);
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT c.*, s.descloja AS marketplace ";
        $sql .= "  FROM campaigns c";
        $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to ";
        $sql .= " WHERE ".$condicao.$orderby." LIMIT 200 OFFSET ".$offset;
        $query = $this->db->query($sql);
        //$this->session->set_flashdata('success',$sql);
        // var_dump($sql);
        return $query->result_array();
    }

    public function getCampaignsViewCount($procura = '')
    {
        $filter = $this->session->userdata('campaignsfilter');
        if ($filter == "") {
            $filter = " AND (c.active != 2 ) "; // default pegar somente os ativos
        }
        $procura = $procura.$filter;

        $condicao = substr(trim($procura.$filter), 4);
        $sql = "SELECT count(*) as qtd FROM campaigns c ";
        $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to ";
        $sql .= ' WHERE '.$condicao;

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('campaigns', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('campaigns');
            return ($delete == true) ? true : false;
        }
    }


    public function getMarketplacesData()
    {
        $sql = "SELECT * FROM stores_mkts_linked WHERE id_integration=13;";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCommissionsCategory($id)
    {
        $sql = "SELECT * FROM campaigns_commission_category WHERE campaigns_id=?;";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function deleteCommissionCategory($id, $category_id)
    {

        $sql = "DELETE FROM campaigns_products WHERE campaigns_id = ? AND category_id = ?";
        $query = $this->db->query($sql, array($id, $category_id));

        $sql = "DELETE FROM campaigns_commission_category WHERE campaigns_id = ? AND category_id = ?";
        $query = $this->db->query($sql, array($id, $category_id));
        return $query;
    }

    public function createCommissionCategory($data)
    {
        if ($data) {
            $insert = $this->db->replace('campaigns_commission_category', $data);
            return $insert;
        }
    }

    public function getCommissionCategoryData($id)
    {

        $sql = "SELECT ccc.*, valor_aplicado, mc.nome AS categoria FROM campaigns_commission_category ccc ";
        $sql .= " LEFT JOIN param_mkt_categ_integ pmci ON pmci.id=ccc.category_id ";
        $sql .= " INNER join param_mkt_categ mc ON mc.id = pmci.mkt_categ_id ";
        $sql .= " WHERE campaigns_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function createCampaignsProducts($campaigns_id, $product_id, $int_to)
    {
        //pego a categoria de acordo com o marketplace e o produto
        $sql = "SELECT distinct MCI.id FROM param_mkt_categ_integ MCI ";
        $sql .= " INNER JOIN param_mkt_categ MC ON MC.id = MCI.mkt_categ_id ";
        $sql .= " INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = MCI.integ_id AND INTG.id_integration=13 ";
        $sql .= " WHERE MC.ativo = 1 and MCI.ativo = 1 AND INTG.apelido = ? AND MC.nome = ";
        $sql .= "    (SELECT replace(SUBSTRING(name, 1, position(' >' in name)-1),'\"','') AS category FROM categories c WHERE c.id = ";
        $sql .= "       (SELECT left(substr(p.category_id,3),length(p.category_id)-4) FROM products p WHERE p.id=?))";

        $query = $this->db->query($sql, array($int_to, $product_id));
        $row = $query->row_array();
        //	$this->session->set_flashdata('success',  $this->db->last_query());
        $category_id = $row['id'];

        $sql = "INSERT INTO campaigns_products (campaigns_id,product_id,category_id,price) ";
        $sql .= " SELECT * FROM (SELECT ? AS campaigns_id, ? AS product_id, ? AS category_id, '' AS price) AS tmp ";
        $sql .= " WHERE NOT EXISTS ( ";
        $sql .= "    SELECT campaigns_id, product_id FROM campaigns_products WHERE campaigns_id =? AND product_id = ? ";
        $sql .= " ) LIMIT 1";
        $query = $this->db->query($sql, array($campaigns_id, $product_id, $category_id, $campaigns_id, $product_id));
        return $query;

    }

    public function deleteCampaignsProducts($campaigns_id, $product_id)
    {
        $sql = "DELETE FROM campaigns_products WHERE campaigns_id = ? AND product_id =?";
        $query = $this->db->query($sql, array($campaigns_id, $product_id));
        return $query;
    }

    public function deleteCampaign($id)
    {
        $sql = "DELETE FROM campaigns_products WHERE campaigns_id = ?";
        $query = $this->db->query($sql, array($id));
        $sql = "DELETE FROM campaigns_commission_category WHERE campaigns_id = ?";
        $query = $this->db->query($sql, array($id));
        $sql = "DELETE FROM campaigns WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query;
    }

    public function deleteCampaignCommissionProducts($id)
    {
        $sql = "DELETE FROM campaigns_products WHERE campaigns_id = ?";
        $query = $this->db->query($sql, array($id));
        $sql = "DELETE FROM campaigns_commission_category WHERE campaigns_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query;
    }

    public function getProductsOnCampaign($campaigns_id, $product_id)
    {
        $sql = "SELECT * FROM campaigns_products WHERE campaigns_id = ? AND product_id =?";
        $query = $this->db->query($sql, array($campaigns_id, $product_id));
        return $query->row_array();
    }

    public function getCommissionsCategoryByProduct($campaigns_id, $product_id)
    {
        $sql = "SELECT * FROM campaigns_commission_category WHERE campaigns_id=? AND category_id = ";
        $sql .= " (SELECT category_id FROM campaigns_products WHERE campaigns_id = ? AND product_id =?)";
        $query = $this->db->query($sql, array($campaigns_id, $campaigns_id, $product_id));
        return $query->row_array();
    }

    public function chanceActive($id, $active)
    {
        if ($id) {
            $this->db->set('active', $active, false);
            $this->db->where('id', $id);
            $update = $this->db->update('campaigns', $data);
            return ($update == true) ? true : false;
        }
    }

    public function getProductsCampaignsData($id, $offset = 0, $orderby, $procura)
    {
        if ($offset == "") {
            $offset = 0;
        }

        $sql = "SELECT p.id as id, p.sku as sku, p.price as price, p.name as name, c.name AS category, s.name AS store, cpy.name AS company, cp.price AS sale, p.store_id AS store_id, p.company_id AS company_id, s.service_charge_value FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= ' LEFT JOIN company cpy on cpy.id = p.company_id ';
        $sql .= ' LEFT JOIN campaigns_products cp on cp.product_id = p.id ';
        $sql .= " WHERE cp.campaigns_id = ? ".$procura.$orderby." LIMIT 200 OFFSET ".$offset;
        $query = $this->db->query($sql, array($id));
        // $this->session->set_flashdata('success',$sql);
        // get_instance()->log_data('getProductsCampaignsData','sql',$this->db->last_query(),"I");

        return $query->result_array();
    }

    public function getProductsCampaignsCount($id, $procura = '')
    {
        if ($procura == "") {
            $sql = "SELECT count(*) as qtd FROM campaigns_products where campaigns_id = ?";
        } else {
            $sql = "SELECT count(*) as qtd FROM products p";
            $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
            $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
            $sql .= ' LEFT JOIN company cpy on cpy.id = p.company_id ';
            $sql .= ' LEFT JOIN campaigns_products cp on cp.product_id = p.id ';
            $sql .= " WHERE cp.campaigns_id = ? ".$procura;

        }
        $query = $this->db->query($sql, array($id));
        //get_instance()->log_data('getProductsCampaignsCount','sql',$this->db->last_query(),"I");
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function activateAndDeactivate()
    {
        // coloco os que estavam planejados como ativo os que já chegaram no dia
        $sql = "UPDATE campaigns SET active=1 WHERE active=4 AND CURDATE() > start_date ";
        $query = $this->db->query($sql);
        // coloco os todoos como inativos quando termina o dia
        $sql = "UPDATE campaigns SET active=2 WHERE active!=2 AND CURDATE() > end_date ";
        $query = $this->db->query($sql);
        return;
    }

    public function getMyProductsOnCampaign($id, $offset = 0, $orderby, $procura)
    {
        if ($offset == "") {
            $offset = 0;
        }
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " and p.company_id = ".$this->data['usercomp'] : " and p.store_id = ".$this->data['userstore']);

        $sql = "SELECT c.*, p.id as product_id, p.name AS produto, p.sku AS sku, p.price, p.qty, s.name AS store, cpy.name AS empresa, ";
        $sql .= " cat.name AS category, p.store_id AS store_id, cp.price as sale, s.service_charge_value as service_charge_value, cp.category_id AS cp_category_id ";
        $sql .= " FROM products p";
        $sql .= " INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
        $sql .= " INNER JOIN campaigns c ON c.id=cp.campaigns_id ";
        $sql .= " LEFT JOIN categories cat on cat.id = left(substr(p.category_id,3),length(p.category_id)-4)";
        $sql .= " LEFT JOIN stores s on s.id = p.store_id ";
        $sql .= " LEFT JOIN company cpy on cpy.id = p.company_id ";
        //$sql.= " WHERE c.id= ? AND (c.active=1 OR c.active=4)  AND p.situacao=2 AND p.status=1 ".$more.$procura.$orderby." LIMIT 20 OFFSET ".$offset;
        $sql .= " WHERE c.id= ? AND p.situacao=2 AND p.status=1 ".$more.$procura.$orderby." LIMIT 200 OFFSET ".$offset;

        $query = $this->db->query($sql, array($id));
        // $this->session->set_flashdata('success',$sql);
        // get_instance()->log_data('getProductsCampaignsData','sql',$this->db->last_query(),"I");

        return $query->result_array();
    }

    public function getMyProductsOnCampaignCount($id, $procura = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " and p.company_id = ".$this->data['usercomp'] : " and p.store_id = ".$this->data['userstore']);

        if ($procura == "") {
            $sql = "SELECT count(*) as qtd FROM products p";
            $sql .= " INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
            $sql .= " INNER JOIN campaigns c ON c.id=cp.campaigns_id ";
            $sql .= " WHERE c.id= ? AND p.situacao=2 AND p.status=1 ".$more;
            //$sql.= " WHERE c.id= ? AND (c.active=1 OR c.active=4)  AND p.situacao=2 AND p.status=1 ".$more;
        } else {
            $sql = "SELECT count(*) as qtd FROM products p";
            $sql .= " INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
            $sql .= " INNER JOIN campaigns c ON c.id=cp.campaigns_id ";
            $sql .= " LEFT JOIN categories cat on cat.id = left(substr(p.category_id,3),length(p.category_id)-4)";
            $sql .= " LEFT JOIN stores s on s.id = p.store_id ";
            $sql .= " LEFT JOIN company cpy on cpy.id = p.company_id ";
            $sql .= " WHERE c.id= ?AND p.situacao=2 AND p.status=1 ".$more.$procura;
            //$sql.= " WHERE c.id= ? AND  (c.active=1 OR c.active=4)  AND p.situacao=2 AND p.status=1 ".$more.$procura;

        }
        $query = $this->db->query($sql, array($id));
        //get_instance()->log_data('getProductsCampaignsCount','sql',$this->db->last_query(),"I");
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getMyProductsOnCampaignWidthOutSaleCount($id = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " and p.company_id = ".$this->data['usercomp'] : " and p.store_id = ".$this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM products p";
        $sql .= " INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
        $sql .= " INNER JOIN campaigns c ON c.id=cp.campaigns_id ";
        $sql .= " WHERE cp.price = '' AND p.situacao=2 AND p.status=1 ".$more;
        if ($id != '') {
            $sql .= " AND c.id=".$id;
        } else {
            $sql .= " AND (c.active=4) ";
        }
        $query = $this->db->query($sql);
        //get_instance()->log_data('getProductsCampaignsCount','sql',$this->db->last_query(),"I");
        $row = $query->row_array();
        return $row['qtd'];
    }


    public function getCampaignsStoreViewData($offset = 0, $procura, $orderby)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = ".$this->data['usercomp'] : " AND p.store_id = ".$this->data['userstore']);

        $filter = $this->session->userdata('campaignsfilter');
        get_instance()->log_data('model_campaigns', '$filter', $filter);
        if ($filter == "") {
            $filter = " AND (c.active != 2) "; // default pegar somente os ativos
        }
        if ($offset == '') {
            $offset = 0;
        }

        $sql = "SELECT c.*, s.descloja AS marketplace ";
        $sql .= "  FROM campaigns c";
        $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to ";
        $sql .= " WHERE c.active != 5 AND c.id IN ";// não mostro os em edição
        $sql .= "    (SELECT cp.campaigns_id FROM products p ";
        $sql .= "       INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
        $sql .= "       WHERE p.situacao=2 AND p.status=1 ".$more." )";
        $sql .= $filter.$procura.$orderby." LIMIT 200 OFFSET ".$offset;

        $query = $this->db->query($sql);
        //var_dump($sql);
        //	get_instance()->log_data('model_campaigns','getCampaignsStoreViewData',$sql);
        //$this->session->set_flashdata('success',$sql);
        // var_dump($sql);
        return $query->result_array();
    }

    public function getCampaignsStoreViewCount($procura = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = ".$this->data['usercomp'] : " AND p.store_id = ".$this->data['userstore']);

        $filter = $this->session->userdata('campaignsfilter');
        if ($filter == "") {
            $filter = " AND (c.active != 2 ) "; // default pegar somente os ativos
        }
        get_instance()->log_data('model_campaigns 2', '$filter', $filter);
        $sql = "SELECT count(*) as qtd FROM campaigns c ";
        $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to ";
        $sql .= " WHERE c.active !=5  AND c.id IN ";    // não mostro os em edição
        $sql .= "    (SELECT cp.campaigns_id FROM products p ";
        $sql .= "       INNER JOIN campaigns_products cp ON cp.product_id=p.id ";
        $sql .= "       WHERE p.situacao=2 AND p.status=1 ".$more." )";
        $sql .= $filter.$procura;
        //$this->session->set_flashdata('success',$sql);
        //	get_instance()->log_data('model_campaigns','getCampaignsStoreViewCount',$sql);
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function updateProductPrice($campaign_id, $product_id, $sale)
    {
        $sql = "UPDATE campaigns_products SET price=? WHERE campaigns_id=? AND product_id = ?";
        $update = $this->db->query($sql, array($sale, $campaign_id, $product_id));
        //$this->session->set_flashdata('success', $this->db->last_query() );
        return $update;
    }

    public function getCampaignByProductId($productId)
    {

        $sql = "SELECT c.*, s.descloja AS marketplace, cp.price as sale ";
        $sql .= "  FROM campaigns c";
        $sql .= " LEFT JOIN stores_mkts_linked s ON s.apelido=c.int_to ";
        $sql .= " LEFT JOIN campaigns_products cp ON cp.campaigns_id=c.id ";
        $sql .= " WHERE (c.active = 1 OR c.active=4) AND cp.price != '' AND cp.product_id = ? ";

        $query = $this->db->query($sql, array($productId));
        //var_dump($sql);
        //	get_instance()->log_data('model_campaigns','getCampaignsStoreViewData',$sql);
        //$this->session->set_flashdata('success',$sql);

        return $query->result_array();
    }

    public function getProductsForCampaignsData($offset = 0, $orderby = '', $procura = '')
    {
        if ($offset == "") {
            $offset = 0;
        }

        $categoriesfilter = $this->session->userdata('cf');

        $sql = "SELECT p.id AS id, p.sku AS sku, p.price AS price, p.name AS name, c.name AS category, s.name AS store, cpy.name AS company, p.store_id AS store_id, p.company_id AS company_id, s.service_charge_value FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= ' LEFT JOIN company cpy on cpy.id = p.company_id ';
        $sql .= " WHERE status = 1 AND situacao = 2 ".$categoriesfilter.$procura.$orderby." LIMIT 200 OFFSET ".$offset;

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function getProductsForCampaignsCount($procura = '')
    {

        $categoriesfilter = $this->session->userdata('cf');

        $sql = "SELECT count(*) as qtd FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= ' LEFT JOIN company cpy on cpy.id = p.company_id ';
        $sql .= " WHERE status = 1 AND situacao = 2".$categoriesfilter.$procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];

    }

    public function getPriceProduct($product_id, $price_default, $int_to)
    {
        // ve se tem uma campanha para este produto ativo
        $sql = "SELECT c.*, cp.price as sale FROM campaigns c ";
        $sql .= " LEFT JOIN campaigns_products cp ON cp.campaigns_id=c.id ";
        $sql .= " WHERE c.active = 1 AND c.int_to = ? AND cp.price != '' AND cp.product_id = ? LIMIT 1";
        $query = $this->db->query($sql, array($int_to, $product_id));
        $campaign = $query->row_array();
        if ($campaign) { // existe então pego o preço da campanha
            return $campaign['sale'];
        }
        return $price_default;

    }
}