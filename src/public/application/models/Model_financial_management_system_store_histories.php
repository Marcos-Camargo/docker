<?php

class Model_financial_management_system_store_histories extends CI_Model
{

    public $tableName = 'financial_management_system_store_histories';

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

    public function createGeneric(int $financialManagementSystemStoresId, string $jobName, string $payload, string $responseJson, int $responseCode): void
    {

        $data = [
            'financial_management_system_stores_id' => $financialManagementSystemStoresId,
            'job_name' => $jobName,
            'payload' => $payload,
            'response_json' => $responseJson,
            'response_code' => $responseCode,
        ];

        $this->create($data);

    }

    public function getByFinancialManagementSystemStoreId(int $financialManagementSystemStoreId): ?array
    {

        $this->db->where('financial_management_system_stores_id', $financialManagementSystemStoreId);
        $this->db->order_by('id', 'DESC');

        return $this->db->get($this->tableName)->result_array();

    }

}
