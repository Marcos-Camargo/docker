<?php

class Model_orders_to_process_commission extends CI_Model
{
    const TABLE = 'orders_to_process_commission';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data)
    {
        $sucess = $this->db->insert(self::TABLE, $data);
        $id = $this->db->insert_id();
        return $sucess ? $id : null;
    }

    public function remove(int $id): bool
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete(self::TABLE);
            return $delete == true;
        }

        return false;
    }

    public function getNextRow(int $id): ?array
    {
        return $this->db->where(array(
            'id >' => $id,
            'status' => 0
        ))->order_by('id', 'ASC')->limit(1)->get(self::TABLE)->row_array();
    }

    public function getByOrder(int $order_id): ?array
    {
        return $this->db->get_where(self::TABLE, array('order_id' => $order_id))->row_array();
    }

}
