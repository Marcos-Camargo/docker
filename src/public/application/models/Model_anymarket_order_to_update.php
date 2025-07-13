<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/

/**
 * Class Model_anymarket_order_to_update
 * @property CI_DB_query_builder $db
 */
class Model_anymarket_order_to_update extends CI_Model
{
    const TABLE = 'anymarket_order_to_update';

    public function __construct()
    {
        parent::__construct();
    }

    public function save($id = 0, $data = [])
    {
        $data['date_update'] = date('Y-m-d H:i:s');
        $data['company_id'] = $data['company_id'] ?? $this->data['usercomp'] ?? 0;
        $data['store_id'] = $data['store_id'] ?? $this->data['userstore'] ?? 0;
        $data['is_new'] = $data['is_new'] ?? 1;
        if ($id == 0) {
            $data['date_create'] = date('Y-m-d H:i:s');
            $this->db->insert(Model_anymarket_order_to_update::TABLE, $data);
            return $this->db->insert_id();
        }
        if ($this->db->update(Model_anymarket_order_to_update::TABLE, $data, ['id' => $id])) {
            return $id;
        }
        return null;
    }

    public function create($data, $searchNotification = true)
    {
        $this->db->trans_begin();
        if ($searchNotification) {
            $where = [
                'order_anymarket_id' => $data['order_anymarket_id'],
                'order_id' => $data['order_id'],
                'is_new' => 1
            ];
            $on_table = $this->db->select('*')->from(Model_anymarket_order_to_update::TABLE)->where($where)->get()->row_array();
            if ($on_table) {
                return $on_table['id'];
            }
        }
        $sucess = $this->db->insert(Model_anymarket_order_to_update::TABLE, $data);
        $id = $this->db->insert_id();
        if ($sucess) {
            $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
        }
        return '' . $id;

    }

    public function getNewOrders($company, $store, $orderId = null)
    {
        //return $this->db->select('*')->from(Model_anymarket_order_to_update::TABLE)->where(['is_new'=>1])->get()->result_array();
        //return $this->db->query("SELECT * FROM anymarket_order_to_update WHERE order_id IN (SELECT id FROM orders WHERE store_id = ?) AND is_new = ?", array($store, true))->result_array();

        $filter = [
                'store_id' => $store,
                'company_id' => $company,
                'is_new' => 1
            ] + ($orderId ? ['order_id' => $orderId] : []);

        return $this->db->select("*, IF(STRCMP(new_status, 'PENDING') = 0,1, IF(STRCMP(new_status, 'PAID') = 0, 2, IF(STRCMP(new_status, 'INVOICED') = 0, 3, IF(STRCMP(new_status, 'SHIPPED') = 0, 4, IF(STRCMP(new_status, 'DELIVERED') = 0, 5, 99))))) AS status_flow ")
            ->from(Model_anymarket_order_to_update::TABLE)
            ->where($filter)
            ->order_by('order_id, status_flow', 'ASC')
            ->get()
            ->result_array();
    }

    public function setIntegrated($id){
        return $this->db->update(Model_anymarket_order_to_update::TABLE,['is_new'=>0],['id'=>$id]);
    }
}
