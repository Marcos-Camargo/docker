<?php

class Model_prd_addon extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAddonData($prd_id = null)
    {
        if ($prd_id) {
            $sql = "SELECT * 
                    FROM prd_addon ad
                    left join prd_to_integration i on i.prd_id = ad.prd_id_addon
                    left join products p on p.id = ad.prd_id_addon
                    WHERE ad.prd_id = ? AND p.status = 1";
            $query = $this->db->query($sql, array($prd_id));
            return $query->result_array();
        }

        return false;
    }

    public function getAddonDataByPrdIdAddOnAndPrdId(int $prd_id_addon, int $prd_id): ?array
    {
        return $this->db->get_where('prd_addon', ['prd_id_addon' => $prd_id_addon, 'prd_id' => $prd_id])->row_array();
    }

    public function removeByPrdIdAddOnAndPrdId(int $prd_id_addon, int $prd_id)
    {
        return $this->db->where(['prd_id_addon' => $prd_id_addon, 'prd_id' => $prd_id])->delete('prd_addon');
    }

    public function create($data, $change = 'Criado')
    {
        if ($data) {
            $insert = $this->db->insert('prd_addon', $data);
            if ($insert) {
                return ($insert == true) ? true : false;
            }
        }
        return false;
    }

    public function remove(int $id): bool
    {
        $this->db->where('prd_id_addon', $id);
        $delete = $this->db->delete('prd_addon');
        return $delete == true;
    }

    public function removeByPrdId(int $prdId): bool
    {
        $this->db->where('prd_id', $prdId);
        $delete = $this->db->delete('prd_addon');
        return $delete == true;
    }
}
