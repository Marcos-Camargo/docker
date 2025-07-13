<?php
/**
 * Class Model_pickup_point
 */

class Model_pickup_point extends CI_Model
{
    protected $table = 'pickup_points';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data)
    {
        if ($data) {
            $insert = $this->db->insert($this->table, $data);
            return $insert ? $this->db->insert_id() : false;
        }

        return false;
    }

    public function create_batch(array $data)
    {
        if ($data) {
            return $this->db->insert_batch($this->table, $data);
        }

        return false;
    }

    public function update(array $data, int $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->table, $data);
            return $update ? $id : false;
        }

        return false;
    }

    public function getById(int $id): ?object
    {
        return $this->db->get_where($this->table, array('id' => $id))->row_object();
    }

    public function getByStoreIdAndActive(int $store_id): array
    {
        return $this->db->get_where($this->table, array('store_id' => $store_id, 'status' => true))->result_array();
    }

}