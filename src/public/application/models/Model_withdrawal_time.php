<?php
/**
 * Class Model_withdrawal_time
 */

class Model_withdrawal_time extends CI_Model
{
    protected $table = 'withdrawal_times';

    public function __construct()
    {
        parent::__construct();
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

    public function deleteByPickupPointId(int $pickup_point_id): bool
    {
        if ($pickup_point_id) {
            return $this->db->delete($this->table, ['pickup_point_id' => $pickup_point_id], 7);
        }

        return false;
    }

    public function getByPickupPointId(int $pickup_point_id, bool $return_array = true): array
    {
        $query = $this->db->where(['pickup_point_id' => $pickup_point_id])->order_by('day_of_week', 'ASC')->get($this->table);
        if ($return_array) {
            return $query->result_array();
        }

        return $query->result_object();
    }

}