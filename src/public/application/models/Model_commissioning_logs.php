<?php

class Model_commissioning_logs extends CI_Model
{

    private $tableName = 'commissioning_logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        return $insert ? $id : false;
    }

    public function getCreationLog($id)
    {
        $this->db->where('model', 'commissionings');
        $this->db->where('method', 'create');
        $this->db->where('model_id', $id);
        $query = $this->db->get($this->tableName);
        return $query->row_array();
    }

    public function getLogs($commisioningId, array $postData)
    {

        $search = $postData['search']['value'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT * FROM ".$this->tableName." WHERE commissioning_id = $commisioningId";
        if ($search) {
            $sql .= "AND data LIKE '%$search%' ";
        }
        $sql .= " ORDER BY $orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function countGetItens($commisioningId, array $postData)
    {

        $search = $postData['search']['value'] ?? null;

        $sql = "SELECT COUNT(*) AS total FROM ".$this->tableName." WHERE commissioning_id = $commisioningId";
        if ($search) {
            $sql .= "AND data LIKE '%$search%' ";
        }

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

}