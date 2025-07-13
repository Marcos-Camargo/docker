<?php
use App\Libraries\Enum\ComissioningType;

class Model_commissioning_trade_policies extends CI_Model
{

    private $tableName = 'commissioning_trade_policies';
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

    public function update(array $data, int $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $update ? $id : false;
    }

    public function getByComissioningId($id)
    {
        $this->db->where('commissioning_id', $id);
        $query = $this->db->get($this->tableName);
        return $query->row_array();
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
        $sql .= $this->generateQueryFromToFindItens($search, $store_id, $int_to, $startDate, $endDate, $status);
        $sql .= " ORDER BY commissionings.start_date ASC, commissionings.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    private function generateQueryFromToFindItens(
        string $search = null,
        int $store_id = null,
        string $int_to = null,
        $startDate = null,
        $endDate = null,
        $status = null
    ): string {
        $sql = ",stores.name as store_name,commissioning_trade_policies.store_id, commissioning_trade_policies.comission FROM commissionings 
        JOIN commissioning_trade_policies ON (commissioning_trade_policies.commissioning_id = commissionings.id)
        JOIN stores ON (stores.id = commissioning_trade_policies.store_id)";
        $sql .= " WHERE commissionings.type = '".ComissioningType::TRADE_POLICY."' ";

        if ($store_id) {
            $sql .= " AND commissioning_trade_policies.store_id = $store_id ";
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

    public function countGetItens(array $postData, int $store_id = null, string $int_to = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $status = $postData['status'] ?? null;
        $endDate = $postData['endDate'] ?? null;

        $sql = "SELECT count(*) total ";
        $sql .= $this->generateQueryFromToFindItens($search, $store_id, $int_to, $startDate, $endDate, $status);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    public function getCommissionByTradePolicyAndStoreAndDateRangeAndIntToToOrder(
        int $sales_channel,
        int $store_id,
        string $order_date,
        string $int_to
    ): ?array {
        return $this->db->select('c.id, ctp.comission')
            ->join('commissionings c', 'c.id = ctp.commissioning_id')
            ->join('vtex_trade_policies vtp', 'vtp.id = ctp.vtex_trade_policy_id')
            ->where(array(
                'vtp.id' => $sales_channel,
                'vtp.int_to' => $int_to,
                'c.int_to' => $int_to,
                'ctp.store_id' => $store_id,
                'c.start_date <=' => $order_date,
                'c.end_date >=' => $order_date
            ))->get('commissioning_trade_policies ctp')
            ->row_array();
    }

    public function getCommissionByTradePolicyAndStoreAndDateRangeAndIntTo(
        array $sales_channel,
        string $dateStart,
        string $dateEnd,
        int $store_id,
        string $int_to
    ): ?array {

        $query = $this->db->select('c.id, ctp.comission')
            ->join('commissionings c', 'c.id = ctp.commissioning_id')
            ->where(array(
                'ctp.store_id' => $store_id,
                'vtp.int_to' => $int_to,
                'c.int_to' => $int_to,
                'c.end_date > ' => dateNow()->format(DATETIME_INTERNATIONAL) //Comissionamento precisa estar vigente
            ))
            ->group_start()
            ->where("('$dateStart' BETWEEN start_date AND end_date)")
            ->or_where("('$dateEnd' BETWEEN start_date AND end_date)")
            ->or_where("(start_date BETWEEN '$dateStart' AND '$dateEnd')")
            ->or_where("(end_date BETWEEN '$dateStart' AND '$dateEnd')")
            ->group_end();

        if ($sales_channel) {
            $ids = [];
            foreach ($sales_channel as $sale_channel) {
                $ids[] = $sale_channel;
            }
            $query = $query->join('vtex_trade_policies vtp', 'vtp.id = ctp.vtex_trade_policy_id')
                ->where_in('vtp.trade_policy_id', $ids);
        }else{
            $query = $query->join('vtex_trade_policies vtp', 'vtp.id = ctp.vtex_trade_policy_id');
        }

        return $query->order_by('ctp.comission', 'asc')
            ->get('commissioning_trade_policies ctp')
            ->result_array();

    }

}