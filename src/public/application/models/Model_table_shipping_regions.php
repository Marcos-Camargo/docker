<?php

class Model_table_shipping_regions extends CI_Model
{

    private $tableName = 'table_shipping_regions';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllActive(): array
    {
        return $this->db
            ->get_where($this->tableName, array('status' => true))
            ->result_array();
    }

    public function removeOldRows($idproviders_to_seller, $id_file): bool
    {
        foreach ($this->getAllActive() as $table_region) {
            $this->db->where(array('idproviders_to_seller' => $idproviders_to_seller, 'id_file !=' => $id_file))->delete($table_region['table']);
        }

        return true;
    }

    public function update(array $data, int $id): bool
    {
        return $this->db->where('id', $id)->update($this->tableName, $data);
    }

    public function getRangesByUf(string $uf): array
    {
        return $this->db
            ->get_where($this->tableName, array('status' => true, 'uf' => $uf))
            ->result_array();
    }

    public function createStateBatch(array $data, string $uf): bool
    {
        return $data && $this->db->insert_batch(str_replace('regions', $uf, $this->tableName), $data);
    }

}