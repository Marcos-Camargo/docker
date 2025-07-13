<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_integrations_migra extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* get the brand data */
    public function getIntegrationsData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM integrations where id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM integrations " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('integrations', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('integrations', $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('integrations');
            return ($delete == true) ? true : false;
        }
    }
    /* get the brand data */
    public function getPrdBestPrice($ean = null)
    {
        if ($ean) {
            $sql = "SELECT price FROM bling_ult_envio where EAN = ?";
            $query = $this->db->query($sql, array($ean));
            $row = $query->row_array();
            if (isset($row)) {
                // if (count($row)>0) {
                $price = $row['price'];
            } else {
                $price = NULL;
            }
            return $price;
        }
        return NULL;
    }
    /* get the brand data */
    public function getPrdIntegrationOLD($id = null, $cpy = null, $status = 0)
    {
        if (($id) && ($cpy)) {
            $sql = "SELECT * FROM prd_to_integration_migra a, integrations b where prd_id = ? AND a.company_id = ? AND status = ? AND a.int_id = b.id ORDER BY b.name";
            $query = $this->db->query($sql, array($id, $cpy, $status));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration_migra " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function setProductToMkt($data)
    {
        if ($data) {
            $replace = $this->db->replace('prd_to_integration_migra', $data);
            return ($replace == true) ? true : false;
        }
    }

    public function unsetProductToMkt($int, $prd, $cpy, $data)
    {
        if ($data) {
            $this->db->where('int_id', $int);
            $this->db->where('prd_id', $prd);
            $this->db->where('company_id', $cpy);
            $update = $this->db->update('prd_to_integration_migra', $data);
            return ($update == true) ? true : false;
        }
    }

    public function ProductToMkt($id)
    {
        if ($id) {
            $this->db->where('prd_id', $id);
            $update = $this->db->update('prd_to_integration_migra', array('status_int' => 0));
            return ($update == true) ? true : false;
        }
    }

    /* get the brand data */
    public function getMktbyStore($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM stores_mkts_linked where id_loja = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        $sql = "SELECT * FROM stores_mkts_linked ORDER BY id id_integration";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getIntegrationsbyCompIntType($company_id, $int_to, $int_from, $int_type, $store_id = 0)
    {

        $sql = "SELECT * FROM integrations WHERE company_id = ? AND int_to = ? AND int_from = ? AND int_type = ? AND store_id = ?";
        $query = $this->db->query($sql, array($company_id, $int_to, $int_from, $int_type, $store_id));
        return $query->row_array();
    }

    public function getIntegrationsbyType($int_type)
    {
        $sql = "SELECT * FROM integrations WHERE active = 1 AND int_type = ? AND int_from = 'CONECTALA' ORDER BY company_id";
        $query = $this->db->query($sql, array($int_type));
        return $query->result_array();
    }

    public function getIntegrationsbyName($name)
    {

        $sql = "SELECT auth_data FROM integrations WHERE active = 1 AND store_id = 0 AND int_to = ? ";
        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    public function changeStatus($int, $prd_id, $store_id, $status, $user_id)
    {

        $sql = "UPDATE prd_to_integration_migra SET status= ?,user_id=? WHERE int_id = ? AND prd_id = ? AND store_id=?";
        $update = $this->db->query($sql, array($status, $user_id, $int, $prd_id, $store_id));
        return $update;
    }

    public function getPrdIntegrationByFields($int_id, $prd_id, $store_id)

    {
        $sql = "SELECT * FROM prd_to_integration_migra WHERE int_id = ? AND prd_id = ? AND store_id = ?";
        $query = $this->db->query($sql, array($int_id, $prd_id, $store_id));
        return $query->row_array();
    }

    public function getPrdIntegrationStore($id = null, $store_id = null, $status = 0)
    {
        if (($id) && ($store_id)) {
            $sql = "SELECT * FROM prd_to_integration_migra a, integrations b where prd_id = ? AND a.store_id = ? AND status = ? AND a.int_id = b.id";
            $query = $this->db->query($sql, array($id, $store_id, $status));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration_migra " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getLogsIntegration($offset = 0, $orderby, $procura)
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND l.company_id = " . $this->data['usercomp'] : " AND l.store_id = " . $this->data['userstore']);

        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT l.*, s.name as store FROM log_integration as l";
        $sql .= " JOIN stores as s ON l.store_id = s.id";
        $sql .= " WHERE l.status = 1 {$more} ";
        $sql .= "{$procura} {$orderby} LIMIT 200 OFFSET " . $offset;
        get_instance()->log_data('JOBINTEGRATION', 'fetchsearch', print_r($sql, true));
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountLogsIntegration($procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND l.company_id = " . $this->data['usercomp'] : " AND l.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM log_integration as l";
        $sql .= " JOIN stores as s ON l.store_id = s.id";
        $sql .= " WHERE l.status = 1 {$more} ";
        $sql .= $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function viewLogsIntegration($id)
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM log_integration";
        $sql .= " WHERE id = {$id} {$more}";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updateIntegrationsbyCompIntType($company_id, $int_to, $int_from, $int_type, $store_id = 0, $auth_data)
    {

        $sql = "UPDATE integrations SET auth_data =?  WHERE company_id = ? AND int_to = ? AND int_from = ? AND int_type = ? AND store_id = ?";
        $query = $this->db->query($sql, array($auth_data, $company_id, $int_to, $int_from, $int_type, $store_id));
        return $query;
    }

    public function getJobsIntegrationStore($stores)
    {
        $sql = "SELECT * FROM job_integration";
        $sql .= " WHERE store_id in ({$stores})";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getJobsIntegration($offset = 0, $orderby, $procura)
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "WHERE j.company_id = " . $this->data['usercomp'] : "WHERE j.store_id = " . $this->data['userstore']);

        if ($more == "" && $procura != "")
            $procura = "WHERE " . substr($procura, 4);

        if ($offset == '') $offset = 0;
        $sql = "SELECT j.*, s.name as store FROM job_integration as j";
        $sql .= " JOIN stores as s ON j.store_id = s.id ";
        $sql .= "{$more} {$procura} {$orderby} LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountJobsIntegration($procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "WHERE j.company_id = " . $this->data['usercomp'] : "WHERE j.store_id = " . $this->data['userstore']);

        if ($more == "" && $procura != "")
            $procura = "WHERE " . substr($procura, 4);

        $sql = "SELECT count(*) as qtd FROM job_integration as j";
        $sql .= " JOIN stores as s ON j.store_id = s.id ";
        $sql .= $more . $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function createJob($data)
    {
        if ($data) {
            $insert = $this->db->insert('job_integration', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function getJobForJobAndStore($job, $store)
    {
        $sql = "SELECT * FROM job_integration ";
        $sql .= "WHERE job='{$job}' AND store_id = $store";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function getJobForId($id)
    {
        $sql = "SELECT * FROM job_integration WHERE id={$id}";
        //echo '<br>L_287_sql_job: '. $sql;

        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updateStatusJobForId($status, $id)
    {
        $sql = "UPDATE job_integration SET status = {$status} WHERE id={$id}";
        //echo '<br>L_296_Model_integrations.php: '.$sql;
        return $this->db->query($sql);
    }

    public function removeJob($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('job_integration');
            return ($delete == true) ? true : false;
        }
    }

    public function getIntegrationsProduct($id = null, $status = 1)
    {;

        // $sql = "SELECT * FROM prd_to_integration_migra WHERE prd_id = ? AND status = ?";
        $sql = "SELECT  p.*, EXISTS(SELECT 1 FROM errors_transformation WHERE prd_id=p.prd_id and status=0 and int_to = p.int_to) AS errors FROM prd_to_integration_migra p WHERE p.prd_id = ? AND p.status = ? AND (p.variant IS NULL OR p.variant=0) ORDER BY int_to";
        $query = $this->db->query($sql, array($id, $status));
        return $query->result_array();
    }

    public function getApiIntegrationStore($store)
    {
        $sql = "SELECT * FROM api_integrations WHERE store_id = {$store}";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updatePrdToIntegration($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('prd_to_integration_migra', $data);
            return ($update == true) ? $id : false;
        }
    }

    public function getPrdIntegrationByIntTo($int_to, $prd_id, $store_id)
    {
        $sql = "SELECT * FROM prd_to_integration_migra a, integrations b where a.int_to = ? AND prd_id = ? AND a.store_id = ? AND  a.int_id = b.id";
        $query = $this->db->query($sql, array($int_to, $prd_id, $store_id));
        return $query->row_array();
    }

    public function getIntegrationByIntTo($int_to, $store_id)
    {
        $sql = "SELECT * FROM integrations where int_to = ? AND store_id = ? AND int_type = 'DIRECT'";
        $query = $this->db->query($sql, array($int_to, $store_id));
        return $query->row_array();
    }

    public function getProductsNeedApproval($offset = 0, $order_by = '', $procura = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if ($order_by == '') {
            $order_by = ' ORDER BY p.sku ';
        }
        $sql = 'SELECT pi.*, p.name, p.sku, p.status AS prdstatus, p.situacao AS prdsituacao, p.qty, p.date_update, s.name AS store, p.date_update, p.image, i.name AS mkt_name';
        $sql .= ' FROM prd_to_integration_migra pi, integrations i, products p, stores s ';
        $sql .= ' WHERE pi.int_id = i.id AND i.auto_approve =false AND p.id=pi.prd_id AND s.id=pi.store_id';
        $sql .= $procura . $order_by . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        //$this->session->set_flashdata('error', $sql);
        return $query->result_array();
    }

    public function getProductsNeedApprovalCount($procura = '')
    {
        $sql = 'SELECT count(*) as qtd ';
        $sql .= ' FROM prd_to_integration_migra pi, integrations i, products p, stores s';
        $sql .= ' WHERE pi.int_id = i.id AND i.auto_approve =false AND p.id=pi.prd_id AND s.id=pi.store_id';
        $sql .= $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getStoresProductsNeedApprovel()
    {
        $sql = 'SELECT * FROM stores WHERE active=1 AND id IN';
        $sql .= ' (SELECT pi.store_id FROM prd_to_integration_migra pi, integrations i WHERE pi.int_id=i.id AND i.auto_approve=false) ';
        $sql .= ' ORDER BY name';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getPrdIntegration($prd_id = null)
    {
        if ($prd_id) {
            $sql = "SELECT a.*, b.name, b.auto_approve FROM prd_to_integration_migra a, integrations b where prd_id = ? AND a.int_id = b.id ORDER BY b.name";
            $query = $this->db->query($sql, array($prd_id));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration_migra " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getIntegrationsbyStore($company_id, $store_id = '0')
    {
        return array();
        $store_id = 0;
        if ($company_id == 1) {
            $company_id = 15;
        }
        if ($store_id == 0) {
            $sql = "SELECT * FROM integrations WHERE company_id =? ORDER BY store_id, int_to LIMIT 4";
            $query = $this->db->query($sql, array($company_id));
        } else {
            $sql = "SELECT * FROM integrations WHERE company_id = ? AND store_id = ? ORDER BY int_to";
            $query = $this->db->query($sql, array($company_id, $store_id));
        }
        return $query->result_array();
    }

    public function getIntegrationsbyStoreId($store_id)
    {
        $sql = "SELECT * FROM integrations WHERE store_id = ? ORDER BY int_to";
        $query = $this->db->query($sql, array($store_id));
        return $query->result_array();
    }

    public function getLogsForStoreAndUniqueId($store, $search)
    {
        return $this->db
            ->from('log_integration')
            ->where(array('store_id' => $store, 'unique_id' => $search))
            ->order_by('date_updated', 'DESC')
            ->get()
            ->result_array();
    }

    public function getIntegrationsContecalaNames($autoaprove = 'all')
    {
        if ($autoaprove == 'all') {
            $sql = 'SELECT DISTINCT(int_to), name FROM integrations WHERE int_type="BLING" ORDER BY name';
            $query = $this->db->query($sql);
        } else {
            $sql = 'SELECT DISTINCT(int_to), name FROM integrations WHERE auto_approve = ? AND int_type="BLING" ORDER BY name';
            $query = $this->db->query($sql, array($autoaprove));
        }
        return $query->result_array();
    }

    public function getNamesIntegrationsbyCompanyStore($company_id, $store_id)
    {
        $more = $this->data['usercomp'] == 1 ? "company_id != 1 AND store_id !=0" : ($this->data['userstore'] == 0 ? "company_id = " . $this->data['usercomp'] : "store_id = " . $this->data['userstore']);

        $sql = 'SELECT DISTINCT(int_to), name FROM integrations WHERE ' . $more . ' ORDER BY name';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getStoreApiIntegration($id)
    {
        $sql = "SELECT store_id FROM api_integrations WHERE id = {$id}";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function removeIntegrationStoreId($store_id)
    {
        if ($store_id) {
            $this->db->where('store_id', $store_id);
            $delete = $this->db->delete('api_integrations');
            return ($delete == true) ? true : false;
        }
    }

    public function removeJobByStore($store_id)
    {
        if ($store_id) {
            $this->db->where('store_id', $store_id);
            $delete = $this->db->delete('job_integration');
            return ($delete == true) ? true : false;
        }
    }

    public function getLogsByStore($store)
    {
        return $this->db
            ->from('log_integration')
            ->where(array('store_id' => $store))
            ->get()
            ->row_array() ? true : false;
    }

    public function removeRowsOrderToIntegration($store)
    {
        // remove registro de orders_to_integration exceto os pedidos ainda não pagos

        // remove os duplicados
        $this->db->query("delete from orders_to_integration where store_id = {$store} and id in
                ( select * from
                    (select id from orders_to_integration ord_int_1 Where (Select count(ord_int_2.order_id)
                        from orders_to_integration ord_int_2
                        where ord_int_2.order_id = ord_int_1.order_id
                        and store_id = {$store}) > 1 and store_id = {$store}
                    ) as order_id
                )");
        // remove os já pagos
        $this->db->query("delete from orders_to_integration where store_id = {$store} and paid_status <> 1");
    }

    public function getIntegrationbyStoreIdAndInto($store_id, $int_to)
    {
        $sql = "SELECT * FROM integrations WHERE store_id = ? AND int_to = ?";
        $query = $this->db->query($sql, array($store_id, $int_to));
        return $query->row_array();
    }

    public function getAllIntegrationsbyType($int_type)
    {
        $sql = "SELECT * FROM integrations WHERE int_type = ? ORDER BY store_id";
        $query = $this->db->query($sql, array($int_type));
        return $query->result_array();
    }

    public function getIntegrations()
    {
        $sql = "SELECT DISTINCT(int_to) FROM prd_to_integration_migra";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsChangedToIntegrate($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, pi.id AS pi_id, pi.skumkt AS pi_skumkt, pi.status as pi_status, pi.status_int as pi_status_int FROM products p, prd_to_integration_migra pi WHERE p.id=pi.prd_id AND p.date_update > pi.date_last_int AND pi.approved = 1 AND pi.int_to='" . $int_to . "'";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsRefresh($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, pi.id AS pi_id, pi.skumkt AS pi_skumkt, pi.status as pi_status, pi.status_int as pi_status_int, pi.variant FROM products p, prd_to_integration_migra pi WHERE p.id=pi.prd_id AND pi.approved = 1 AND pi.int_to='" . $int_to . "'";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getNewProductsToIntegrate($int_to, $catalog_id, $offset = 0, $limit = 20)
    {

        $sql = "SELECT * FROM products WHERE qty > 0 AND has_variants = '' AND status=1 AND situacao = 2 AND id NOT IN (SELECT prd_id FROM prd_to_integration_migra WHERE int_to=?) AND product_catalog_id IN ( SELECT product_catalog_id FROM catalogs_products_catalog c WHERE c.catalog_id =? AND c.product_catalog_id = products.product_catalog_id )";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to, $catalog_id));
        return $query->result_array();
    }

    public function getNewProductsVariantsToIntegrate($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, v.variant FROM products p, prd_variants v WHERE p.id = v.prd_id AND p.qty > 0 AND p.has_variants != '' AND p.status=1 AND p.situacao = 2 AND p.id NOT IN (SELECT prd_id FROM prd_to_integration_migra WHERE variant = v.variant AND int_to='" . $int_to . "')";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function createPrdToIntegration($data)
    {
        if ($data) {
            $insert = $this->db->insert('prd_to_integration_migra', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
        return false;
    }

    public function getDifferentVariant($int_to, $productId, $variant)
    {

        $sql = "SELECT * FROM prd_to_integration_migra WHERE int_to = ? AND prd_id = ? AND variant != ? AND mkt_product_id IS NOT NULL ORDER BY variant LIMIT 1";
        $query = $this->db->query($sql, array($int_to, $productId, $variant));
        return $query->row_array();
    }

    public function createIfNotExist($prd_id, $int_to, $variant, $data)
    {
        if (is_null($variant)) {
            $sql = "SELECT id FROM prd_to_integration_migra WHERE int_to= ? AND prd_id= ? AND variant is null";
            $query = $this->db->query($sql, array($int_to, $prd_id));
        } else {
            $sql = "SELECT id FROM prd_to_integration_migra WHERE int_to= ? AND prd_id= ? AND variant = ?";
            $query = $this->db->query($sql, array($int_to, $prd_id, $variant));
        }

        $row = $query->row_array();
        if (!$row) {
            return $this->createPrdToIntegration($data);
        }
        return $this->updatePrdToIntegration($data, $row['id']);
    }

    public function getMyNamesIntegrations($hub = false)
    {
        if ($hub) {
            $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

            $sql = 'SELECT DISTINCT(int_to), name FROM integrations WHERE int_from="HUB" ' . $more . ' ORDER BY name';
        } else {
            $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

            $sql = 'SELECT DISTINCT(int_to), name FROM integrations ' . $more . ' ORDER BY name';
        }

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToPublish($offset = 0, $order_by = '', $procura = '', $limit = 200)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if ($order_by == "") {
            $order_by = 'GROUP BY p.id ORDER BY p.date_create desc ';
        }

        $sql = "SELECT p.*, s.name AS store FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration_migra i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        //	$sql.= " WHERE p.status=1 and p.omnilogic_status in ('RECEIVED', 'IMPORTED') and p.dont_publish != true";
        $sql .= " WHERE p.status=1 and p.dont_publish != true";
        $sql .= $procura . $more . " " . $order_by . " LIMIT " . (int)$limit . "  OFFSET " . $offset;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToPublishCount($procura = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(distinct(p.id)) as qtd FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration_migra i ON i.prd_id = p.id ";
        $sql .= " WHERE p.status=1 and p.dont_publish != true";
        // $sql.= " WHERE p.status=1 and p.omnilogic_status in ('RECEIVED', 'IMPORTED') and p.dont_publish != true";
        $sql .= $more . $procura;

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getPrdIntegrationByIntToProdId($int_to, $product_id)
    {
        $sql = "SELECT * FROM prd_to_integration_migra WHERE int_to=? AND prd_id = ? LIMIT 1";
        $query = $this->db->query($sql, array($int_to, $product_id));
        return $query->row_array();
    }

    public function getIntegrationsProductAll($id = null)
    {
        $sql = "SELECT  p.*, EXISTS(SELECT 1 FROM errors_transformation WHERE prd_id=p.prd_id and status=0 and int_to = p.int_to) AS errors FROM prd_to_integration_migra p WHERE p.prd_id = ? AND (p.variant IS NULL OR p.variant=0) ORDER BY int_to";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getIntegrationsProductWithVariant($prd_id, $int_to, $variant)
    {
        if (is_null($variant)) {
            $sql = "SELECT * FROM prd_to_integration_migra WHERE int_to= ? AND prd_id= ? AND variant is null";
            $query = $this->db->query($sql, array($int_to, $prd_id));
        } else {
            $sql = "SELECT * FROM prd_to_integration_migra WHERE int_to= ? AND prd_id= ? AND variant = ?";
            $query = $this->db->query($sql, array($int_to, $prd_id, $variant));
        }

        return $query->row_array();
    }

    public function get_integrations_list()
    {
        $sql = "select * from integrations where int_type='DIRECT' and store_id=0;";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToCheckMarketplace($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT * FROM prd_to_integration_migra WHERE status=1 AND status_int = 22 AND int_to=? ORDER BY store_id";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
    }

    public function getVtexData($store_id, $company_id)
    {
        $sql = "SELECT * FROM integrations where store_id=? and company_id = ?";
        $query = $this->db->query($sql, array($store_id, $company_id));
        return $query->result_array();
    }

    public function getPrdIntegrationHub($prd_id)
    {

        $sql = "SELECT a.*, b.name, b.auto_approve FROM prd_to_integration_migra a, integrations b where prd_id = ? AND a.int_id = b.id AND b.int_from='HUB' ORDER BY b.name";
        $query = $this->db->query($sql, array($prd_id));
        return $query->result_array();
    }

    public function getIntegrationsByIntTo($int_to)
    {
        $sql = "SELECT * FROM integrations WHERE int_to = ?";
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
    }

    public function getPrdIntegrationByIntToStatus($int_to, $status, $status_int, $offset = 0, $limit = 200)
    {
    	
		if (is_array($status_int)) {
			$sta = ' AND (';
			foreach ($status_int as $sint) {
				$sta.= 'status_int='.$sint.' OR ';
			}
			$sta = substr($sta,0,-4).')';
		}
		else {
			$sta = ' AND status_int = '.$status_int; 
		}
        $sql = "SELECT * FROM prd_to_integration_migra WHERE int_to = ? AND status = ? ".$sta;
        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to, $status));
        return $query->result_array();
    }

    public function removeOrderToIntegrationByOrderAndStatus($order, $status)
    {
        if (!$order || !$status) return false;

        return (bool)$this->db->where([
            'order_id' => $order,
            'paid_status' => $status
        ])->delete('orders_to_integration');
    }

	public function removePrdToIntegration($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('prd_to_integration_migra');
            return ($delete == true) ? true : false;
        }
    }
	
	public function getPrdToIntegration($id)
    {
       	$sql = "SELECT * FROM integrations WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }
    
    public function getPrdToIntegrationBySkyblingAndIntto($skubling, $int_to)
    {
    	$sql = "SELECT * FROM prd_to_integration_migra WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
		return $query->row_array();
    }
	
	 public function getPrdToIntegrationBySkyblingAndInttoMulti($skubling, $int_to)
    {
    	$sql = "SELECT * FROM prd_to_integration_migra WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
		return $query->result_array();
    }

}
