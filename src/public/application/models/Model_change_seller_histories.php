<?php

class Model_change_seller_histories extends CI_Model
{
    const TABLE_NAME = 'change_seller_histories';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data): bool
    {
        if ($data) {
            return $this->db->insert(self::TABLE_NAME, $data);
        }

        return false;
    }

    public function getByOrderId(int $order_id): array
    {
        return $this->db->where('order_id', $order_id)->get(self::TABLE_NAME)->result_array();
    }

    public function getByOrderIdWithOldStoreNameAndIgnoreOldStoreId(int $order_id, int $ignore_old_store_id): array
    {
        return $this->db
            ->select(self::TABLE_NAME . '.*, s.name as old_store_name')
            ->join('stores s', self::TABLE_NAME . '.old_store_id = s.id')
            ->where('order_id', $order_id)
            ->where('old_store_id !=', $ignore_old_store_id)
            ->get(self::TABLE_NAME)
            ->result_array();
    }

}