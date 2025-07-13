<?php
use App\Libraries\Enum\ComissioningType;

class Model_commissioning_products extends CI_Model
{

    private $tableName = 'commissioning_products';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CommissioningLogs');
        $this->createLog = new CommissioningLogs();
    }

    public function create(array $data)
    {

        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $insert ? $id : false;
    }

    public function updateByPk(array $data, int $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $update ? $id : false;
    }

    public function update(int $comissioningId, int $productId, int $paymentMethodId, int $tradePolicyId, $newComission)
    {

        $data = [
            'comission' => $newComission,
            'commissioning_id' => $comissioningId,
        ];

        $this->db->where('product_id', $productId);
        $this->db->where('vtex_payment_method_id', $paymentMethodId);
        $this->db->where('vtex_trade_policy_id', $tradePolicyId);
        $this->db->where('commissioning_id', $comissioningId);
        $this->db->update($this->tableName, $data);

        $this->createLog->log($data, null, $this->tableName, __FUNCTION__);

        return true;
    }

    public function delete(int $comissioningId, int $productId, int $paymentMethodId, int $tradePolicyId)
    {
        $this->db->where('product_id', $productId);
        $this->db->where('vtex_payment_method_id', $paymentMethodId);
        $this->db->where('vtex_trade_policy_id', $tradePolicyId);
        $this->db->where('commissioning_id', $comissioningId);

        $this->db->delete($this->tableName);

        $this->createLog->log([
            'commissioning_id' => $comissioningId, 'product_id' => $productId,
            'vtex_payment_method_id' => $paymentMethodId, 'vtex_trade_policy_id' => $tradePolicyId
        ], null, $this->tableName, __FUNCTION__);

        return true;

    }

    public function getCommissionByProductAndPaymentMethodAndStoreAndDateRange(
        string $int_to,
        int $product_id,
        array $method_payment,
        int $sales_channel,
        int $store_id,
        string $order_date
    ): ?array {

        if (!$method_payment || !$sales_channel) {
            return [];
        }

        return $this->db->select('c.id, cp.comission')
            ->join('commissionings c', 'c.id = cp.commissioning_id')
            ->join('vtex_payment_methods vpm', 'cp.vtex_payment_method_id = vpm.id')
            ->where(array(
                'cp.product_id' => $product_id,
                'cp.store_id' => $store_id,
                'c.int_to =' => $int_to,
                'c.start_date <=' => $order_date,
                'c.end_date >=' => $order_date,
                'cp.vtex_trade_policy_id' => $sales_channel,
            ))
            ->where_in('vpm.method_name', $method_payment)
            ->get('commissioning_products cp')
            ->row_array();
    }

    public function getCommissionByProductAndDateRange(
        string $int_to,
        int $product_id,
        $method_payment,
        $sales_channel,
        string $dateStart,
        string $dateEnd
    ): ?array {

        $wheres = [
            'cp.product_id' => $product_id,
            'c.int_to =' => $int_to,
            'c.end_date > ' => dateNow()->format(DATETIME_INTERNATIONAL) //Comissionamento precisa estar vigente
        ];

        $query = $this->db->select('cp.comission')
            ->join('commissionings c', 'c.id = cp.commissioning_id', 'left')
            ->where($wheres)
            ->group_start()
            ->where("('$dateStart' BETWEEN start_date AND end_date)")
            ->or_where("('$dateEnd' BETWEEN start_date AND end_date)")
            ->or_where("(start_date BETWEEN '$dateStart' AND '$dateEnd')")
            ->or_where("(end_date BETWEEN '$dateStart' AND '$dateEnd')")
            ->group_end();

        if ($method_payment) {

            $query = $query->join('vtex_payment_methods vpm', 'cp.vtex_payment_method_id = vpm.id');

            $ids = [];
            foreach ($method_payment as $method) {
                $ids[] = $method['method_id'];
            }

            $query = $query->where_in('vpm.method_id', $ids);
        }

        if ($sales_channel) {

            $query = $query->join('vtex_trade_policies vtp', 'cp.vtex_trade_policy_id = vtp.id');

            $ids = [];
            foreach ($sales_channel as $sale_channel) {
                $ids[] = $sale_channel;
            }

            $query = $query->where_in('vtp.trade_policy_id', $ids);
        }

        return $query->order_by('cp.comission', 'asc')
            ->get('commissioning_products cp')->result_array();

    }

    public function getItens(array $postData, int $store_id = null, string $int_to = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;
        $status = $postData['status'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT commissionings.* ";
        $sql .= $this->generateQueryFromToFindItens($search, $store_id, $int_to, null, true, $startDate, $endDate,
            $status);
        $sql .= " ORDER BY commissionings.start_date ASC, commissionings.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    private function generateQueryFromToFindItens(
        string $search = null,
        int $store_id = null,
        string $int_to = null,
        int $commisioningId = null,
        $groupByStore = false,
        $startDate = null,
        $endDate = null,
        $status = null
    ): string {
        $sql = ",stores.name as store_name,commissioning_products.store_id, commissioning_products.comission ";
        if ($commisioningId) {
            $sql .= " , vtex_payment_methods.method_name, 
                        commissioning_products.vtex_payment_method_id, 
                        vtex_trade_policies.trade_policy_name, 
                        commissioning_products.vtex_trade_policy_id, 
                        commissioning_products.product_id, 
                        products.sku ";
        }
        $sql .= " FROM commissionings
                    JOIN commissioning_products ON (commissioning_products.commissioning_id = commissionings.id)
                    JOIN products ON (commissioning_products.product_id = products.id)
                    JOIN vtex_payment_methods ON (vtex_payment_methods.id = commissioning_products.vtex_payment_method_id)
                    JOIN vtex_trade_policies ON (vtex_trade_policies.id = commissioning_products.vtex_trade_policy_id)
                    JOIN stores ON (stores.id = commissioning_products.store_id)";
        $sql .= " WHERE commissionings.type = '".ComissioningType::PRODUCT."' ";

        if ($commisioningId) {
            $sql .= " AND commissionings.id = $commisioningId";
        }

        if ($store_id) {
            $sql .= " AND commissioning_products.store_id = $store_id ";
        }
        if ($int_to) {
            $sql .= " AND commissionings.int_to = '$int_to' ";
        }

        if ($search) {

            $search = addslashes($search);

            $sql .= " AND ( ";
            $sql .= "  commissionings.name LIKE '%$search%' ";
            $sql .= " ) ";

        }

        if ($store_id || $groupByStore) {
            $sql .= " GROUP BY commissionings.id";
        }

        if ($startDate) {
            $sql .= " AND commissionings.start_date >= '$startDate 00:00:00' ";
        }
        if ($endDate) {
            $sql .= " AND commissionings.end_date <= '$endDate 23:59:59' ";
        }

        if ($status == 'scheduled') {
            $sql .= " AND commissionings.start_date > NOW() ";
        }
        if ($status == 'expired') {
            $sql .= " AND commissionings.end_date <= NOW() ";
        }
        if ($status == 'active') {
            $sql .= " AND commissionings.start_date <= NOW() AND commissionings.end_date > NOW() ";
        }

        return $sql;

    }

    public function getByComissioningId($id)
    {
        $this->db->where('commissioning_id', $id);
        $query = $this->db->get($this->tableName);
        return $query->result_array();
    }

    public function getItensById($id)
    {

        $sql = "SELECT commissionings.* ";
        $sql .= $this->generateQueryFromToFindItens(null, null, null, $id);

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function countGetItens(array $postData, int $store_id = null, string $int_to = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;
        $status = $postData['status'] ?? null;

        $sql = "SELECT COUNT(DISTINCT commissionings.id) AS total ";
        $sql .= $this->generateQueryFromToFindItens($search, $store_id, $int_to, null, false, $startDate, $endDate,
            $status);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

}