<?php
/*
 
 Model de Acesso ao log de pedidos integrados de marketplaces  para Integracoes
 
 */

/**
 * Class Model_log_integration
 * @property \CI_DB_query_builder $db
 */
class Model_log_integration extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param   array       $data   Retorna dados do log ou nulo caso não encontre
     * @return  array|null
     */
    public function getLogByData(array $data): ?array
    {
        $whereKey   = array();
        $whereValue = array();
        foreach ($data as $key => $value){
            array_push($whereKey, "$key = ?");
            array_push($whereValue, $value);
        }

        return $this->db->query(" SELECT * FROM `log_integration` WHERE " . implode(' AND ', $whereKey), $whereValue)->row_array();
    }
    
    public function create(array $data): bool
    {
        if($data) {
            $insert = $this->db->insert('log_integration', $data);
            return $insert == true;
        }
        return false;
    }
    
    public function update(array $data, int $id): bool
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('log_integration', $data);
            return $update == true;
        }
        return false;
    }

    public function getLogByCriteria(array $data): ?array
    {
        $where = [];
        if (isset($data['startDate'])) {
            $where['log.date_updated >='] = $data['startDate'];
        }
        if (isset($data['endDate'])) {
            $where['log.date_updated <='] = $data['endDate'];
        }
        if (isset($data['store_id']) && $data['store_id'] > 0) {
            $where['log.store_id'] = $data['store_id'];
        }
        $this->db
            ->select("log.*, s.name as store_name")
            ->from("log_integration log")
            ->join("stores s", "log.store_id = s.id")
            ->join("(SELECT MAX(date_updated) as date_updated FROM log_integration WHERE status = 1 and store_id=".$data["store_id"]." GROUP BY store_id,unique_id) As l2", "log.date_updated = l2.date_updated")
            ->where($where)
            ->group_by("log.unique_id")
            ->order_by("log.date_updated","DESC");
            
        if(isset($data['types']) && !empty($data['types'])) {
            $this->db->where_in('log.type', $data['types']);
        }
        return $this->db->get()->result_array();
    }

    public function getLastLog(int $store_id, int $order_id): ?object
    {
        return $this->db->select('id, title, description, date_updated, `type`')
            ->where(
                [
                    'store_id'  => $store_id,
                    'unique_id' => $order_id
                ]
            )
            ->where_in('job', array('UpdateStatus', 'ApiOrderInvoiced', 'ApiOrderDelivered', 'ApiOrderCanceled', 'ApiOrderShipped'))
            ->where('date_updated BETWEEN (CURRENT_TIMESTAMP - INTERVAL 3 DAY) AND CURRENT_TIMESTAMP', NULL, FALSE)
            ->order_by('date_updated, id', 'DESC')
            ->limit(1)
            ->get('log_integration')
            ->row_object();
    }

    public function log($data): bool
    {
        try {
            $this->db->select(['*'])
                ->from('log_integration USE INDEX (ix_log_integration_01)')
                ->where([
                    'store_id' => $data['store_id'],
                    'company_id' => $data['company_id'],
                    'job' => $data['job'],
                    'unique_id' => (string)$data['unique_id'],
                ])->limit(1);
            $log = $this->db->get()->row_array();
            $data['id'] = $log['id'] ?? 0;
            if (empty($data['id'])) {
                $data['date_created'] = date('Y-m-d H:i:s');
                return $this->create($data);
            }
            $data['date_updated'] = date('Y-m-d H:i:s');
            return $this->db->update('log_integration', $data, ['id' => $data['id']]);
        } catch (Throwable $e) {
            return false;
        }
    }
}