<?php

/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

class Model_control_sequential_skumkts extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

    }

    public function updateAutoIncrement(int $auto_increment)
    {
        $sql = "ALTER TABLE control_sequential_skumkts AUTO_INCREMENT = ?";
        return $this->db->query($sql, [$auto_increment]);
    }

    public function create(array $data)
    {
        $this->db->insert('control_sequential_skumkts', $data);
        return $this->db->insert_id();
    }

    public function removeEmptyRow(string $int_to)
    {
        return $this->db->where('prd_id IS NULL', NULL, FALSE)
            ->delete('control_sequential_skumkts', array('int_to' => $int_to));
    }

    public function getByPrdVariantIntTo(int $prd_id, ?int $variant, string $int_to): ?array
    {
        $this->db->where(array(
            'prd_id' => $prd_id,
            'int_to' => $int_to
        ));

        if (is_null($variant)) {
            $this->db->where('variant IS NULL', null, false);
        } else {
            $this->db->where('variant', $variant);
        }

        return $this->db->get('control_sequential_skumkts')->row_array();
    }

    public function checkIfExistProductPublished(string $int_to): bool
    {
        return $this->db->where('prd_id IS NOT NULL', null, false)
            ->where('int_to', $int_to)
            ->limit(1)
            ->get('control_sequential_skumkts')->num_rows() > 0;
    }
}