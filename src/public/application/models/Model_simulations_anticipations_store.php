<?php

use App\Libraries\Enum\AnticipationStatusEnum;

class Model_simulations_anticipations_store extends CI_Model
{

    private $tableName = 'simulations_anticipations_store';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        return $insert ? $id : false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            return ($update == true) ? $id : false;
        }
    }

    public function findByStoreId(int $storeId): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['store_id' => $storeId])->get()->result_array();
    }

    public function findBuildingByStoreId(int $storeId): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['store_id' => $storeId, 'anticipation_status' => AnticipationStatusEnum::BUILDING])->get()->result_array();
    }

    public function findPending(): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['anticipation_status' => AnticipationStatusEnum::PENDING])->get()->result_array();
    }

    public function findByPk(int $pk): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['id' => $pk])->get()->row_array();
    }

    public function getTotalsAnticipatedByStoreId(array $storeIds): ?array
    {

        return $this->db->select('sum(amount) as total_anticipated, 
        sum(anticipation_fee) as total_anticipation, 
        sum(fee) as total_fee, 
        sum(anticipation_fee) + sum(fee) as total_taxes')
            ->from($this->tableName)
            ->where_in('anticipation_status', ["pending", "approved"])
            ->where_in('store_id', $storeIds)
            ->get()->row_array();

    }

}