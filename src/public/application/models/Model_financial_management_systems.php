<?php

class Model_financial_management_systems extends CI_Model
{

    public const NIBO = 'Nibo';

    public $tableName = 'financial_management_systems';

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

    public function getId(string $name): ?int
    {

        $this->db->where('name', $name);
        $this->db->limit(1, 0);

        $row = $this->db->get($this->tableName)->row_array();

        if ($row){
            return $row['id'];
        }

        return null;

    }

}
