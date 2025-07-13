<?php

class Model_financial_management_system_stores extends CI_Model
{

    public $tableName = 'financial_management_system_stores';

    public function __construct()
    {
        parent::__construct();

    }

    public function create(array $data): bool
    {
        $insert = $this->db->insert($this->tableName, $data);
        return $insert == true;
    }

    public function update(array $data, int $id): bool
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);
        return $update == true;
    }

    public function remove(int $id): bool
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete($this->tableName);
        return $delete == true;
    }

    public function getByStoreIdFinancialManagementSystemId(int $storeId, int $financialManagementSystemId): ?array
    {

        $this->db->where('store_id', $storeId);
        $this->db->where('financial_management_system_id', $financialManagementSystemId);
        $this->db->limit(1, 0);

        $row = $this->db->get($this->tableName)->row_array();

        return $row;

    }

    public function getByStoreId(int $storeId): ?array
    {

        $this->db->where('store_id', $storeId);
        $this->db->limit(1, 0);

        $row = $this->db->get($this->tableName)->row_array();

        return $row;

    }

    public function createGeneric(int $systemId, int $storeId, string $status): void
    {

        $data = [
            'financial_management_system_id' => $systemId,
            'store_id' => $storeId,
            'status' => $status,
        ];

        $this->create($data);

    }

}
