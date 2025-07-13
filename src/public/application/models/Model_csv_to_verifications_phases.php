<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

 */
class Model_csv_to_verifications_phases extends CI_Model
{
    const TABLE_NAME = 'csv_to_verification_phases';
    public function __construct()
    {
        parent::__construct();
    }
    public function create(array $data): bool
    {
        if ($data) {
            return $this->db->insert(self::TABLE_NAME, $data);
        }
    }
    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update(self::TABLE_NAME, $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete(self::TABLE_NAME);
            return ($delete == true) ? true : false;
        }
    }
    public function getDontChecked($checked = false)
    {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE checked = ?";
        $query = $this->db->query($sql, array($checked));
        return $query->result_array();
    }
    public function setChecked($id = null, $situation)
    {
        $data = ['final_situation' => $situation, 'checked' => '1'];
        return $this->db->update(self::TABLE_NAME, $data, ['id' => $id]);
        // $query = $this->db->query($sql, array($situation, $id));
    }
}
