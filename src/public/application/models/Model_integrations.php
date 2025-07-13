<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

/**
 * Class Model_integrations
 * @property CI_DB_query_builder $db
 */
class Model_integrations extends CI_Model
{

    private const TABLE = 'integrations';

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
            return ($insert) ? true : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('integrations', $data);
            return ($update) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('integrations');
            return ($delete) ? true : false;
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
            $sql = "SELECT * FROM prd_to_integration a, integrations b where prd_id = ? AND a.company_id = ? AND status = ? AND a.int_id = b.id ORDER BY b.name";
            $query = $this->db->query($sql, array($id, $cpy, $status));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function setProductToMkt($data)
    {
        if ($data) {
            $replace = $this->db->replace('prd_to_integration', $data);
            return ($replace) ? true : false;
        }
    }

    public function unsetProductToMkt($int, $prd, $cpy, $data)
    {
        if ($data) {
            $this->db->where('int_id', $int);
            $this->db->where('prd_id', $prd);
            $this->db->where('company_id', $cpy);
            $update = $this->db->update('prd_to_integration', $data);
            return ($update) ? true : false;
        }
    }

    public function ProductToMkt($id)
    {
        if ($id) {
            $this->db->where('prd_id', $id);
            $update = $this->db->update('prd_to_integration', array('status_int' => 0));
            return ($update) ? true : false;
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

    public function getIntegrationsbyTypeAndFrom($int_type, $int_from)
    {
        $sql = "SELECT * FROM integrations WHERE active = 1 AND int_type = ? AND int_from = ? ORDER BY company_id";
        $query = $this->db->query($sql, array($int_type, $int_from));
        return $query->result_array();
    }

    public function getIntegrationsbyName($name)
    {

        $sql = "SELECT auth_data FROM integrations WHERE active = 1 AND store_id = 0 AND int_to = ? ";
        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    public function changeStatus($int, $prd_id, $store_id, $status, $user_id, $status_int = null)
    {
        if (is_null($status_int)) {
            $sql = "UPDATE prd_to_integration SET status= ?,user_id=? WHERE int_id = ? AND prd_id = ? AND store_id=?";
            $update = $this->db->query($sql, array($status, $user_id, $int, $prd_id, $store_id));
		}
		else {
            $sql = "UPDATE prd_to_integration SET status= ?,user_id=?, status_int=?  WHERE int_id = ? AND prd_id = ? AND store_id=?";
            $update = $this->db->query($sql, array($status, $user_id, $status_int, $int, $prd_id, $store_id));
        }

        return $update;
    }

    public function getPrdIntegrationByFields($int_id, $prd_id, $store_id)

    {
        $sql = "SELECT * FROM prd_to_integration WHERE int_id = ? AND prd_id = ? AND store_id = ?";
        $query = $this->db->query($sql, array($int_id, $prd_id, $store_id));
        return $query->row_array();
    }

    public function getPrdIntegrationStore($id = null, $store_id = null, $status = 0)
    {
        if (($id) && ($store_id)) {
            $sql = "SELECT * FROM prd_to_integration a, integrations b where prd_id = ? AND a.store_id = ? AND status = ? AND a.int_id = b.id";
            $query = $this->db->query($sql, array($id, $store_id, $status));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getLogsIntegration($offset , $orderby, $procura,$store)
    {
        if (!$offset) {$offset = 0;}

        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND l.company_id = " . $this->data['usercomp'] : " AND l.store_id = " . $this->data['userstore']);

        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT l.*, s.name as store FROM log_integration_unique as l";
        $sql .= " JOIN stores as s ON l.store_id = s.id";
        $sql .= " WHERE l.status = 1 {$more} ";
        $sql .= "{$procura} {$orderby} LIMIT 200 OFFSET " . $offset;

        //get_instance()->log_data('JOBINTEGRATION', 'fetchsearch', print_r($sql, true));
        $query = $this->db->query($sql);
        return $query->result_array();
    }

	public function getCountLogsIntegration($procura = '', $store = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND l.company_id = " . $this->data['usercomp'] : " AND l.store_id = " . $this->data['userstore']);

        $sql = "SELECT COUNT(DISTINCT unique_id) as qtd FROM log_integration_unique as l";
        $sql .= " JOIN stores as s ON l.store_id = s.id";
        $sql .= " WHERE l.status = 1 {$more} ";
        $sql .= $procura;
        $sql .= "LIMIT 1";
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

    public function updateIntegrationsbyCompIntType($company_id, $int_to, $int_from, $int_type, $store_id = 0, $auth_data='')
    {

        $sql = "UPDATE integrations SET auth_data =?  WHERE company_id = ? AND int_to = ? AND int_from = ? AND int_type = ? AND store_id = ?";
        return $this->db->query($sql, array($auth_data, $company_id, $int_to, $int_from, $int_type, $store_id));

    }

    public function getJobsIntegrationStore($stores)
    {
        $sql = "SELECT * FROM job_integration";
        $sql .= " WHERE store_id in ({$stores})";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getJobsIntegration($offset, $orderby, $procura)
    {

        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "WHERE j.company_id = " . $this->data['usercomp'] : "WHERE j.store_id = " . $this->data['userstore']);

        if ($more == "" && $procura != "") {
            $procura = "WHERE " . substr($procura, 4);
        }

        if (!$offset) {$offset = 0;}
        $sql = "SELECT j.*, s.name as store FROM job_integration as j";
        $sql .= " JOIN stores as s ON j.store_id = s.id ";
        $sql .= "{$more} {$procura} {$orderby} LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountJobsIntegration($procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "WHERE j.company_id = " . $this->data['usercomp'] : "WHERE j.store_id = " . $this->data['userstore']);

        if ($more == "" && $procura != "") {
            $procura = "WHERE " . substr($procura, 4);
        }
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
            return ($insert) ? true : false;
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

        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updateStatusJobForId($status, $id)
    {
        $sql = "UPDATE job_integration SET status = {$status} WHERE id={$id}";
        return $this->db->query($sql);
    }

    public function removeJob($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('job_integration');
            return ($delete) ? true : false;
        }
    }

    public function getIntegrationsProduct($id = null, $status = 1)
    {

        $sql = "SELECT  p.*, EXISTS(SELECT 1 FROM errors_transformation WHERE prd_id=p.prd_id and status=0 and int_to = p.int_to) AS errors FROM prd_to_integration p WHERE p.prd_id = ? AND p.status = ? GROUP BY int_to ORDER BY int_to";
        $query = $this->db->query($sql, array($id, $status));
        return $query->result_array();
    }

    public function getApiIntegrationStore($store)
    {
        $sql = "SELECT * FROM api_integrations WHERE store_id = {$store}";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updatePrdToIntegration($data, $id, $products = '', $error_id = '', $data_prd_to_integration = null)
    {
        if ($data && $id) {

            if (is_null($data_prd_to_integration)) {
                $data_prd_to_integration = $this->db->where('id', $id)->get('prd_to_integration')->row_array();
            }

            if(array_key_exists('approved', $data) && $data['approved'] == 1 && empty($data_prd_to_integration['approved_curatorship_at'])){
                $data['approved_curatorship_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            if(empty($data_prd_to_integration['skumkt']) && !empty($data['skumkt'])){
                $data['published_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            $this->db->where('id', $id);
            $this->db->limit('100');
            $update = $this->db->update('prd_to_integration', $data);

            if(!array_key_exists('approved', $data)){
                return $id;
            }

            if($data['approved'] == 1){
                $product_id = $this->db->select('prd_id, int_to')->from('prd_to_integration')->where(['id'=>$id])->get()->row_array();

                $this->db->where([
                    'prd_id' => $product_id['prd_id'],
                    'int_to' => $product_id['int_to']
                ])->update('errors_transformation', ['status' => 1]);

                $this->db->delete('errors_transformation', ['prd_id' => $product_id['prd_id'], 'int_to' => $product_id['int_to']]);

                return $this->db->delete('products_errors', ['product_id' => $product_id['prd_id'], 'int_to' => $product_id['int_to']]);
            }

            if($data['approved'] == 3){

                $product_id = $this->db->select('prd_id, int_to')->from('prd_to_integration')->where(['id'=>$id])->get()->row_array();

                $this->db->where([
                    'prd_id' => $product_id['prd_id'],
                    'int_to' => $product_id['int_to']
                ])->update('errors_transformation', ['status' => 1]);

                $this->db->delete('errors_transformation', ['prd_id' => $product_id['prd_id'], 'int_to' => $product_id['int_to']]);

                return $this->db->delete('products_errors', ['product_id' => $product_id['prd_id'], 'int_to' => $product_id['int_to']]);
            }

            if(empty($products)){
                return;
            }

            if($data['approved'] == 2){
                $arr3        = '';
                $errorReason = '';

                foreach($products as $key => $product){
                    // REMOVE OS DADOS EXISTENTES PARA PRESCREVER
                    $this->db->where(['product_id' => $product['product_id'], 'int_to' => $product['int_to']]);
                    $delete = $this->db->delete('products_errors');

                    if($delete){

                        $body = [
                            "product_id" => $product['product_id'],
                            "int_to"     => $product['int_to'],
                            "comment"    => $product['comment'],
                            "error_id"   => $product['error_id']
                        ];

                        $this->db->insert('products_errors', $body);

                        switch ($product['error_id']) {
                            case 1:
                                $errorReason = 'Sem imagem';
                                break;
                            case 2:
                                $errorReason = 'Sem categoria';
                                break;
                            case 3:
                                $errorReason = 'Sem dimensões';
                                break;
                            case 4:
                                $errorReason = 'Sem preço';
                                break;
                            case 5:
                                $errorReason = 'Sem descrição';
                                break;
                        }
                        $errorReasons  = [];
                        if($key < count($products) - 1){
                            $errorReason .= ', ';
                        }

                        if(($key == count($products) - 1) && !empty($product['comment'])){
                            $errorReason .= ', ';
                        }


                        if($key < count($products) - 1){
                            $errorReason .= ', ';
                        }

                        if(($key == count($products) - 1) && !empty($product['comment'])){
                            $errorReason .= ', ';
                        }

                        array_push($errorReasons, $errorReason);
                        $arr2 = implode(" ",$errorReasons);
                        if(count($error_id) >= 2) {

                            $res = $this->db->select('*')
                                ->from('errors_transformation')
                                ->where([
                                    'prd_id' => $product['product_id'],
                                    'int_to' => $product['int_to']
                                ])
                                ->get()
                                ->row_array();

                            if($res > 1){
                                if(strpos($arr3,$arr2) === false){
                                    $arr3 .= $arr2;
                                }
                            }else{
                                $arr3 = $arr2;
                            }

                        }else{
                            $arr3 = $arr2;
                        }
                        $bodyMgs = "Produto reprovado na Curadoria. Motivo(s): {$arr3} ";

                        $tableErrosTransform = $this->db
                            ->where([
                                'prd_id' => $product['product_id'],
                                'int_to' => $product['int_to']
                            ])
                            ->get('errors_transformation')
                            ->result_array();

                        $payloadToErrorsTransformation = [
                            "message" =>  $bodyMgs . $product['comment'],
                            "step"    => "Aprovação de produtos",
                            "skumkt"  => $product['sku'],
                            "status"  => 0,
                            "prd_id"  => $product['product_id'],
                            "int_to"  => $product['int_to']
                        ];

                        if(!$tableErrosTransform){
                            $this->db->insert('errors_transformation', $payloadToErrorsTransformation);
                        } else {
                            $this->db->where([
                                'prd_id' => intval($product['product_id']),
                                'int_to' => $product['int_to']
                            ]);
                            $this->db->update('errors_transformation', $payloadToErrorsTransformation);
                        }
                    }
                }
            }
            return ($update) ? $id : false;
        }
        return false;
    }

    public function updatePrdToIntegrationByPrdId(array $data, int $prd_id, string $int_to)
    {
        if ($data && $prd_id) {
            return $this->db->where(array('prd_id' => $prd_id, 'int_to' => $int_to))
                ->update('prd_to_integration', $data) ? $prd_id : false;
        }
    }

    public function getPrdIntegrationByIntTo($int_to, $prd_id, $store_id)
    {
        $sql = "SELECT * FROM prd_to_integration a, integrations b where a.int_to = ? AND prd_id = ? AND a.store_id = ? AND  a.int_id = b.id";
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

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if ($order_by == '') {
            $order_by = ' ORDER BY p.sku ';
        }

        $sql = 'SELECT pi.*, p.name, p.sku, p.status AS prdstatus, p.situacao AS prdsituacao, p.qty, p.date_update, p.category_id, p.principal_image, p.qty ,s.name
        AS store, s.service_charge_value AS comissao, p.date_update, p.image, i.name AS mkt_name, c.name AS name_category
        FROM prd_to_integration pi
        LEFT JOIN integrations i ON i.int_to = pi.int_to
        LEFT JOIN products p ON p.id = pi.prd_id
        LEFT JOIN stores s ON s.id = p.store_id
        LEFT JOIN categories c ON c.id = left(substr(p.category_id,3),length(p.category_id)-4)
        LEFT JOIN prd_variants pv ON pv.prd_id = p.id
        LEFT JOIN control_sync_skuseller_skumkt csss ON csss.store_id = p.store_id AND (csss.skuseller = p.sku OR csss.skuseller = pv.sku)
        JOIN brands b ON b.id = left(substr(p.brand_id,3),length(p.brand_id)-4) AND b.active = 1
        WHERE pi.int_id = i.id AND i.auto_approve =false AND p.id=pi.prd_id AND s.id=pi.store_id AND p.status = ? AND p.situacao = ?';
        $sql .= $more . $procura . ' GROUP BY p.id, pi.int_to' . $order_by . " LIMIT  $limit  OFFSET  $offset ";
        $query = $this->db->query($sql, array(Model_products::ACTIVE_PRODUCT, Model_products::COMPLETE_SITUATION));
        return $query->result_array();
    }

    public function getProductsNeedApprovalCount($procura = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = 'SELECT p.id, pi.int_to FROM prd_to_integration pi';
        $sql .= ' LEFT JOIN integrations i ON i.int_to = pi.int_to';
        $sql .= ' LEFT JOIN products p ON p.id = pi.prd_id';
        $sql .= ' LEFT JOIN stores s ON s.id = p.store_id';
        $sql .= ' LEFT JOIN categories c ON c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN prd_variants pv ON pv.prd_id = p.id';
        $sql .= ' LEFT JOIN control_sync_skuseller_skumkt csss ON csss.store_id = p.store_id AND (csss.skuseller = p.sku OR csss.skuseller = pv.sku)';
        $sql .= ' JOIN brands b ON b.id = left(substr(p.brand_id,3),length(p.brand_id)-4) AND b.active = 1';
        $sql .= ' WHERE pi.int_id = i.id AND i.auto_approve =false AND p.id=pi.prd_id AND s.id=pi.store_id AND p.status = ? AND p.situacao = ?';
        $sql .= $more . $procura . ' GROUP BY p.id, pi.int_to';
        $query = $this->db->query($sql, array(Model_products::ACTIVE_PRODUCT, Model_products::COMPLETE_SITUATION));
        return $query->num_rows();
    }

    public function getStoresProductsNeedApprovel()
    {
        $sql = 'SELECT * FROM stores WHERE active=1 AND id IN';
        $sql .= ' (SELECT pi.store_id FROM prd_to_integration pi, integrations i WHERE pi.int_id=i.id AND i.auto_approve=false) ';
        $sql .= ' ORDER BY name';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getPrdIntegration($prd_id = null)
    {
        if ($prd_id) {
            $sql = "SELECT a.*, b.name, b.auto_approve FROM prd_to_integration a, integrations b where prd_id = ? AND a.int_id = b.id ORDER BY b.name";
            $query = $this->db->query($sql, array($prd_id));
            return $query->result_array();
        }
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];
        $sql = "SELECT * FROM prd_to_integration " . $more . " ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getIntegrationsbyStore($company_id, $store_id = '0')
    {

        if ($store_id == 0) {
            $sql = "SELECT * FROM integrations WHERE company_id = ? ORDER BY store_id, int_to LIMIT 4";
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
            ->order_by('date_updated,id', 'DESC')
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
            return ($delete) ? true : false;
        }
    }

    public function removeJobByStore($store_id)
    {
        if ($store_id) {
            $this->db->where('store_id', $store_id);
            $delete = $this->db->delete('job_integration');
            return ($delete) ? true : false;
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
        $sql = "SELECT DISTINCT(int_to) FROM prd_to_integration";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsChangedToIntegrate($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, pi.id AS pi_id, pi.skumkt AS pi_skumkt, pi.status as pi_status, pi.status_int as pi_status_int FROM products p, prd_to_integration pi WHERE p.id=pi.prd_id AND p.date_update > pi.date_last_int AND pi.approved = 1 AND pi.int_to='$int_to' AND p.status != 3";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsRefresh($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, pi.id AS pi_id, pi.skumkt AS pi_skumkt, pi.status as pi_status, pi.status_int as pi_status_int, pi.variant FROM products p, prd_to_integration pi WHERE p.id=pi.prd_id AND pi.approved = 1 AND pi.int_to='" . $int_to . "'";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getNewProductsToIntegrate($int_to, $catalog_id, $offset = 0, $limit = 20)
    {

        $sql = "SELECT * FROM products WHERE qty > 0 AND has_variants = '' AND status=1 AND situacao = 2 AND id NOT IN (SELECT prd_id FROM prd_to_integration WHERE int_to=?) AND product_catalog_id IN ( SELECT product_catalog_id FROM catalogs_products_catalog c WHERE c.catalog_id =? AND c.product_catalog_id = products.product_catalog_id )";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to, $catalog_id));
        return $query->result_array();
    }

    public function getNewProductsVariantsToIntegrate($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT p.*, v.variant FROM products p, prd_variants v WHERE p.id = v.prd_id AND p.qty > 0 AND p.has_variants != '' AND p.status=1 AND p.situacao = 2 AND p.id NOT IN (SELECT prd_id FROM prd_to_integration WHERE variant = v.variant AND int_to='" . $int_to . "')";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function createPrdToIntegration($data)
    {
        if ($data) {

            if(!empty($data['skumkt'])){
                $data['published_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            $insert = $this->db->insert('prd_to_integration', $data);
            return ($insert) ? $this->db->insert_id() : false;
        }
        return false;
    }

    public function getDifferentVariant($int_to, $productId, $variant)
    {

        $sql = "SELECT * FROM prd_to_integration WHERE int_to = ? AND prd_id = ? AND variant != ? AND mkt_product_id IS NOT NULL ORDER BY variant LIMIT 1";
        $query = $this->db->query($sql, array($int_to, $productId, $variant));
        return $query->row_array();
    }

    public function createIfNotExist($prd_id, $int_to, $variant, $data)
    {
        if (is_null($variant)) {
            $sql = "SELECT id, skumkt, published_at, approved_curatorship_at FROM prd_to_integration WHERE int_to= ? AND prd_id= ? AND variant is null";
            $query = $this->db->query($sql, array($int_to, $prd_id));
        } else {
            $sql = "SELECT id, skumkt, published_at, approved_curatorship_at FROM prd_to_integration WHERE int_to= ? AND prd_id= ? AND variant = ?";
            $query = $this->db->query($sql, array($int_to, $prd_id, $variant));
        }

        $row = $query->row_array();

        if (!$row) {
            return $this->createPrdToIntegration($data);
        }
        return $this->updatePrdToIntegration($data, $row['id'], '', '', $row);
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

    public function getAllIntegrations($hub): array
    {

        if ($hub) {
            $this->db->where('int_from', "HUB");
        }

        if ($this->data['usercomp'] != 1) {
            if (($this->data['userstore'] == 0)) {
                $this->db->where('company_id', $this->data['usercomp']);
            } else {
                $this->db->where('store_id', $this->data['userstore']);
            }
        }

        $this->db->distinct();
        $this->db->select('int_to, name');
        $this->db->where('active', 1);
        $this->db->order_by('name', 'asc');

        return $this->db->get('integrations')->result_array();
    }

    public function getProductsToPublish($offset = 0, $order_by = '', $procura = '', $limit = 200)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if ($order_by == "") {
            $order_by = 'GROUP BY p.id ORDER BY p.id desc ';
        }

        $sql = "SELECT p.*, s.name AS store FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= " WHERE p.dont_publish != true";
        $sql .= $procura . $more . " " . $order_by . " LIMIT " . (int)$limit . "  OFFSET " . (int)$offset;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToPublishCount($procura = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(distinct(p.id)) as qtd FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= " WHERE p.dont_publish != true";
        $sql .= $more . $procura;

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getPrdIntegrationByIntToProdId($int_to, $product_id, $variant = null): ?array
    {
        $where = array(
            'int_to' => $int_to,
            'prd_id' => $product_id
        );

        if ($variant !== null) {
            $where['variant'] = $variant;
        }

        return $this->db->where($where)->order_by('skumkt', 'DESC')->limit(1)->get('prd_to_integration')->row_array();
    }

    public function getIntegrationsProductAll($id = null, $onlyone = false)
    {
        $sql = "SELECT p.*, EXISTS(SELECT 1 FROM errors_transformation WHERE prd_id=p.prd_id and status=0 and int_to = p.int_to) AS errors FROM prd_to_integration p WHERE p.prd_id = ? AND (p.variant IS NULL OR p.variant=0) ORDER BY int_to";
        if ($onlyone) {
            $sql = "SELECT p.*, EXISTS(SELECT 1 FROM errors_transformation WHERE prd_id=p.prd_id and status=0 and int_to = p.int_to) AS errors FROM prd_to_integration p WHERE p.prd_id = ? ORDER BY int_to, p.variant ";
            $sql .= " LIMIT 1 ";
        }
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getIntegrationsProductWithVariant($prd_id, $int_to, $variant)
    {
        if (is_null($variant)) {
            $sql = "SELECT * FROM prd_to_integration WHERE int_to= ? AND prd_id= ? AND variant is null";
            $query = $this->db->query($sql, array($int_to, $prd_id));
        } else {
            $sql = "SELECT * FROM prd_to_integration WHERE int_to= ? AND prd_id= ? AND variant = ?";
            $query = $this->db->query($sql, array($int_to, $prd_id, $variant));
        }

        return $query->row_array();
    }

    public function getIntegrationsProductByIntTo($prd_id, $int_to)
    {
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

            $sql = "SELECT a.*, b.name, b.auto_approve 
                    FROM prd_to_integration a, integrations b 
                    where prd_id = ? 
                      AND a.int_id = b.id 
                      AND b.int_from='HUB'
                      AND a.int_to = '$int_to'
                    AND a.mkt_sku_id IS NOT NULL
                    ORDER BY b.name";

        }else{

            //@todo pode remover
            $sql = "SELECT a.*, b.name, b.auto_approve 
                    FROM prd_to_integration a, integrations b 
                    where prd_id = ? 
                      AND a.int_id = b.id 
                      AND b.int_from='HUB'
                      AND a.int_to = '$int_to'
                    ORDER BY b.name";

        }
        $query = $this->db->query($sql, array($prd_id));
        return $query->result_array();
    }

    public function get_integrations_list()
    {
        $sql = "select * from integrations where int_type='DIRECT' and store_id=0;";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToCheckMarketplace($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT * FROM prd_to_integration USE INDEX (status_store) WHERE status=1 AND (status_int = 22 OR (skumkt IS NOT null AND mkt_sku_id IS NULL)) AND int_to=? ORDER BY store_id";

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

        $sql = "SELECT a.*, b.name, b.auto_approve FROM prd_to_integration a, integrations b where prd_id = ? AND a.int_id = b.id AND b.int_from='HUB' ORDER BY b.name";
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
        $sql = "SELECT * FROM prd_to_integration WHERE int_to = ? AND status = ? ".$sta;
        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to, $status));
        return $query->result_array();
    }

    public function removeOrderToIntegrationByOrderAndStatus($order, $status)
    {
        if (!$order || !$status) {return false;}

        return (bool)$this->db->where([
            'order_id' => $order,
            'paid_status' => $status
        ])->delete('orders_to_integration');
    }

    public function removePrdToIntegration($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('prd_to_integration');
            return ($delete) ? true : false;
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
        $sql = "SELECT * FROM prd_to_integration WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
        return $query->row_array();
    }

    public function getPrdToIntegrationBySkyblingAndInttoMulti($skubling, $int_to)
    {
        $sql = "SELECT * FROM prd_to_integration WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
        return $query->result_array();
    }

    public function getPrdToIntegrationById($id)
    {
        $sql = "SELECT * FROM prd_to_integration WHERE id = ?";
		$query = $this->db->query($sql,array($id));
        return $query->row_array();
    }

    public function getPrdIntegrationByFieldsMulti($int_id, $prd_id, $store_id)

    {
        $sql = "SELECT * FROM prd_to_integration WHERE int_id = ? AND prd_id = ? AND store_id = ?";
        $query = $this->db->query($sql, array($int_id, $prd_id, $store_id));
        return $query->result_array();
    }


    public function getIntegrationsbyStoreIdActive($store_id, $intTo = null)
    {

        if($intTo == null){
            $this->db->where('active', 1);
        } else{
            $this->db->where('int_to', $intTo);
        }

        $this->db->where('store_id', $store_id);
        $this->db->order_by('int_to', 'asc');
        $query = $this->db->get('integrations');
        return $query->result_array();
    }

    public function countProductIntegrations($prd_id)
    {
        $sql = "SELECT count(*) as qtd FROM prd_to_integration WHERE prd_id = ? AND skumkt is not NULL";
        $query = $this->db->query($sql, array($prd_id));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getAllPrdToIntegrationByInto($int_to, $offset = 0, $limit = 20)
    {

        $sql = "SELECT * FROM prd_to_integration WHERE int_to=? ORDER BY store_id, prd_id";

        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
    }

	public function getFecthIntegrations($int_to, $offset = 0, $limit = 200, $orderby='', $procura='')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        if ($offset == '') {$offset = 0;}
        $sql = "SELECT i.*, s.name as store , c.name as company, s.service_charge_value as service_charge_value, s.service_charge_freight_value as service_charge_freight_value FROM integrations i, stores s, company as c";
        $sql .= " WHERE i.store_id = s.id AND i.company_id = c.id ";
        $sql .= " AND i.int_to =  '".$int_to."'";
        $sql .= "{$more} {$procura} {$orderby} LIMIT {$limit} OFFSET {$offset}" ;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountFecthIntegrations($int_to,$procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM integrations i, stores s, company as c";
        $sql .= " WHERE i.store_id = s.id AND i.company_id = c.id ";
        $sql .= " AND i.int_to =  '".$int_to."'";
        $sql .= $more . $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

	public function getActivetStoresWithOutIntegration($int_to, $associate_type=null)
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND s.company_id = " . $this->data['usercomp'] : "AND s.id = " . $this->data['userstore']);

        $sql = "SELECT s.* FROM stores s ";
		if ($associate_type==null) {
            $sql .= " WHERE s.active = 1 {$more} AND s.id NOT IN (SELECT store_id FROM integrations WHERE int_to = ? )";
            $query = $this->db->query($sql, array($int_to));
        } else {
            $sql .= " WHERE s.active = 1 {$more} AND associate_type != ? AND s.id NOT IN (SELECT store_id FROM integrations WHERE int_to = ? )";
			$query = $this->db->query($sql, array($associate_type , $int_to));
        }

        return $query->result_array();
    }

    public function verifySellerId($int_to, $auth_data, $store_id)
    {
        $sql = "SELECT * FROM integrations WHERE int_to = ? AND auth_data = ? AND store_id != ?";
        $query = $this->db->query($sql, array($int_to, $auth_data, $store_id));
        return $query->row_array();
    }

    public function getStoreByMKTSeller($int_to, $seller_id)
    {
		$auth_data = '%"seller_id":"'.$seller_id.'"%';
        $sql = "SELECT s.* FROM stores s, integrations i WHERE i.int_to = ? AND auth_data like ? AND i.store_id = s.id";
        $query = $this->db->query($sql, array($int_to, $auth_data));
        return $query->row_array();
    }

    public function getAllDistinctIntTo(int $store_id = null): array
    {
        $sql = 'SELECT DISTINCT(int_to), int_to,name FROM integrations';

        if (!is_null($store_id)) {
            $sql .= " WHERE store_id = '$store_id'";
        }

        $sql .= ' ORDER BY name';

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function updateJobIntegrationById($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('job_integration', $data);
            return ($update) ? $id : false;
        }
    }

    public function getFecthIntegrationsWithoutIntTo($int_to, $offset = 0, $limit = 200, $orderby='', $procura='')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        if ($offset == '') {$offset = 0;}
        $sql = "SELECT '".$int_to."' as int_to, s.id as id, sih.seller_index as seller_index, s.name as store , c.name as company, ";
        $sql .= " s.service_charge_value as service_charge_value, s.service_charge_freight_value as service_charge_freight_value, ";
        $sql .= " s.active as active FROM stores s, company as c, seller_index as sih ";
        $sql .= " WHERE s.company_id = c.id AND s.active = 1 AND s.id = sih.store_id ";
        $sql .= " AND s.id NOT IN (SELECT store_id FROM integrations WHERE int_to =  '".$int_to."') ";

        $sql .= "{$more} {$procura} {$orderby} LIMIT {$limit} OFFSET {$offset}" ;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountFecthIntegrationsWithoutIntTo($int_to,$procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM stores s, company as c, seller_index as sih";
        $sql .= " WHERE s.company_id = c.id AND s.active = 1 AND s.id = sih.store_id ";
        $sql .= " AND s.id NOT IN (SELECT store_id FROM integrations WHERE int_to =  '".$int_to."') ";
        $sql .= $more . $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getFecthIntegrationsIndex($int_to, $offset = 0, $limit = 200, $orderby='', $procura='')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        if ($offset == '') {$offset = 0;}
        $sql = " SELECT i.*, s.name as store , sih.seller_index as seller_index, c.name as company, s.service_charge_value as service_charge_value, s.service_charge_freight_value as service_charge_freight_value FROM integrations i ";
        $sql .= " LEFT JOIN stores s ON (i.store_id = s.id AND s.active = 1) ";
        $sql .= " LEFT JOIN company as c ON (i.company_id = c.id) ";
        $sql .= " LEFT JOIN seller_index as sih ON (i.store_id = sih.store_id) ";
        $sql .= " WHERE i.int_to =  '".$int_to."' AND i.store_id != 0 ";
        $sql .= "{$more} {$procura} {$orderby} LIMIT {$limit} OFFSET {$offset}" ;
        $query = $this->db->query($sql);

        return $query->result_array();
    }

    public function getCountFecthIntegrationsCountIndex($int_to,$procura = '')
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? "AND i.company_id = " . $this->data['usercomp'] : "AND i.store_id = " . $this->data['userstore']);

        $sql = " SELECT count(*) as qtd FROM integrations i ";
        $sql .= " LEFT JOIN stores s ON (i.store_id = s.id AND s.active = 1) ";
        $sql .= " LEFT JOIN company as c ON (i.company_id = c.id) ";
        $sql .= " LEFT JOIN seller_index as sih ON (i.store_id = sih.store_id) ";
        $sql .= " AND i.int_to =  '".$int_to."'";
        $sql .= $more . $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }


    public function getGatewaySubAccountData($store_id){
        if(is_null($store_id))
            return false;
        $sql = "SELECT gateway_account_id, name 
        JOIN payment_gateways pg ON pg.id = gs.gateway_id 
        FROM gateway_subaccounts gs WHERE store_id = ?";
        $account = $this->db->query($sql, array($store_id))->row();
        if(!$account)
            return false;

        return $account;
    }



    public function getIntegrationsbyTypeAndFromIntTo($int_type, $int_from, $int_to)
    {
        $sql = "SELECT * FROM integrations WHERE active = 1 AND int_type = ? AND int_from = ? AND int_to= ? ORDER BY company_id DESC";
        $query = $this->db->query($sql, array($int_type, $int_from, $int_to));
        return $query->result_array();
    }

    public function getIntegrationsByCriteria(array $criteria = [])
    {
        return $this->db->select('int.*')
            ->from('integrations int')
            ->where($criteria)
            ->get()->result_array();
    }

    public function getIntegrationsDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if($procura == ''){
            $procura = " WHERE int_type = 'DIRECT' ";
        }
        $sql = "SELECT * FROM integrations ";
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getIntegrationsDataCount($procura = '', $offset = 0, $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if($procura == ''){
            $procura = " WHERE int_type = 'DIRECT' ";
        }
        $sql = "SELECT count(*) as qtd FROM integrations ";
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getRegisterIntegration($id)
    {

        $hasRegister = $this->model_integrations_settings->verifyExistRegister($id);

        if($hasRegister == 0){

           return $this->db->get_where('integrations i', ['id' => $id])->row_array();
        }

        return $this->db
            ->select(
                [
                    'i.id',
                    'i.name',
                    'i.active',
                    'i.store_id',
                    'i.company_id',
                    'i.auth_data',
                    'i.int_type',
                    'i.int_from',
                    'i.int_to',
                    'i.auto_approve',
                    'i.mkt_type',
                    'is.tradesPolicies',
                    'is.adlink',
                    'is.update_product_specifications',
                    'is.update_sku_specifications',
                    'is.update_sku_vtex',
                    'is.update_product_vtex',
                    'is.integration_id',
                    'is.auto_approve as auto_approve_two',
                    'is.minimum_stock', 
                    'is.ref_id',
                    'is.reserve_stock', 
                    'is.hasAuction',
                    'is.update_images_specifications',
                    'is.skumkt_default',
                    'is.skumkt_sequential_initial_value',
                ]
            )
            ->from('integrations i')
            ->join('integrations_settings is', 'i.id = is.integration_id')
            ->where('is.integration_id', $id)
            ->get()
            ->row_array();
    }

    public function createIntegration($id = '', $request)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->update('integrations', $request);
        }
        return $this->db->insert('integrations', $request);
    }

    public function RemoveRegisterIntegration($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('integrations');
        return true;
    }

    public function getIntegrationActive()
    {
        return $this->db->where('store_id', 0)->where('active', 1)->get('integrations');
    }

    public function getIntegrationSettings($int_to)
    {
        return $this->db->where('int_to', $int_to)
            ->join('integrations_settings', 'integrations_settings.integration_id = integrations.id')
            ->get('integrations')->row_object();
    }

    public function verifyIntegration($request)
    {
        $sql = "select int_to FROM integrations where int_to = UPPER('{$request}')";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function getProductsRepare($procura)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT 
        p.id,
        pi.prd_id,
        pi.id as prd_integration_id,
        pi.approved,
        pi.int_to,
        p.name, 
        p.sku,
        p.status,
        p.peso_bruto,
        p.largura,
        p.altura,
        p.profundidade,
        p.products_package,
        p.peso_liquido,
        p.list_price ,
        p.price,
        p.qty ,
        p.codigo_do_fabricante,
        p.EAN,
        p.category_id, 
        p.brand_id, 
        p.image ,
        p.principal_image ,
        p.description,
        p.has_variants
         FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration pi ON pi.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= " WHERE p.status != 3";
        $sql .= $procura . $more . " LIMIT 100";

        $query = $this->db->query($sql);
        return $query->row_array();

    }

    public function getTradePolicies($int_to){
        $sql = "select * FROM vtex_trade_policies WHERE int_to = LOWER(?) AND active = ?";
        $query = $this->db->query($sql, array($int_to, 1));
        return $query->result_array();
    }

    public function updatePrdToIntegrationByTrusteeship(array $data, int $id, array $products = array(), $error_id = array())
    {
        $prd_to_integration = $this->db->where('id', $id)->get('prd_to_integration')->row_array();

        if ($prd_to_integration) {
            foreach ($this->db->where(array(
                'prd_id' => $prd_to_integration['prd_id'],
                'int_id' => $prd_to_integration['int_id']
            ))->get('prd_to_integration')->result_array() as $prd_to_integration) {
                $this->updatePrdToIntegration($data, $prd_to_integration['id'], $products, $error_id, $prd_to_integration);
            }
        }
    }

    public function getIntegrationsByCatalogName($catalog_name)
    {

        if(empty($catalog_name)){
            return null;
        }

        return $this->db->where('store_id', 0)
            ->where('int_to', $catalog_name)
            ->get(self::TABLE)
            ->row();

    }


    public function getIntegrationWake($seller_id)
    {
        $sql = "SELECT store_id, JSON_EXTRACT(auth_data, '$.seller_id') AS seller_id
                FROM integrations
                WHERE JSON_EXTRACT(auth_data, '$.seller_id') = ?";

       
        $query = $this->db->query($sql, array($seller_id));

        return $query->result_array();
    }

    public function getIntegrationFarmWake($mkt_type)
    {
        
        $sql = "SELECT * 
                FROM integrations 
                WHERE store_id = 0 
                AND mkt_type = ? limit 1";
    
        $query = $this->db->query($sql, array($mkt_type));
        return $query->result_array();
    }
  
    public function checkIfExistProductPublished(string $int_to): bool
    {
        return $this->db->where('int_to', $int_to)
                ->where('skumkt IS NOT NULL', null, false)
                ->limit(1)
                ->get('prd_to_integration')
                ->num_rows() > 0;
    }

    public function checkIfExistProductPublishedByPrdAndIntto(int $prd_id, string $int_to): bool
    {
        return $this->db->where('int_to', $int_to)
                ->where('prd_id', $prd_id)
                ->where('skumkt IS NOT NULL', null, false)
                ->limit(1)
                ->get('prd_to_integration')
                ->num_rows() > 0;
    }

    public function checkIfExistProductByPrd(int $prd_id): bool
    {
        return $this->db->where('prd_id', $prd_id)
                ->get('prd_to_integration')
                ->num_rows() > 0;
    }

    public function getIntegrationsbyCompIntTypeWake($company_id, $int_to, $store_id)
    {
        $sql = "SELECT * FROM integrations WHERE company_id = ? AND int_to = ? AND store_id = ?";
        $query = $this->db->query($sql, array($company_id, $int_to, $store_id));
    
        return $query->row_array();
    }

    public function getIntegrationsbyCompanyId($company_id)
    {
        $sql = "SELECT auth_data FROM integrations WHERE company_id = ?";
        $query = $this->db->query($sql, array($company_id));
        return $query->result_array();
    }

    public function getProductsToDisapprovedByStoreId(int $store_id): array
    {
        return $this->db->get_where('prd_to_integration', array(
            'store_id' => $store_id,
            'approved !=' => 1
        ))->result_array();
    }

    public function updateProductsToApprovedByStoreId(int $store_id): bool
    {
        return $this->db->update('prd_to_integration', array(
            'approved' => 1
        ), array(
            'store_id' => $store_id,
            'approved !=' => 1
        ));
    }

}
