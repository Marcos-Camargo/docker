<?php

use App\Libraries\Enum\OrderRefundMassiveStatus;

class Model_order_refund_massive extends CI_Model
{

    private $tableName = 'order_refund_massive';

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

    public function update(array $data, int $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);

        return $update ? $id : false;
    }

    public function getList(array $postData)
    {

        $search = $postData['search']['value'] ?? null;
        $user = $postData['user'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT {$this->tableName}.* ";
        $sql .= $this->generateQueryFromToGetList($search, $user);
        $sql .= " ORDER BY {$this->tableName}.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function getUserList()
    {

        $sql = "SELECT {$this->tableName}.user FROM {$this->tableName} GROUP BY {$this->tableName}.user ";
        $sql .= " ORDER BY {$this->tableName}.user ASC ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function countTotalList(array $postData)
    {

        $search = $postData['search']['value'] ?? null;
        $user = $postData['user'] ?? null;

        $sql = "SELECT count(DISTINCT {$this->tableName}.id) total ";
        $sql .= $this->generateQueryFromToGetList($search, $user);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    private function generateQueryFromToGetList(
        string $search = null,
        string $user = null
    ): string {

        $user = addslashes($user);

        $sql = " FROM {$this->tableName} ";
        if ($user) {
            $sql .= " WHERE {$this->tableName}.user = '$user' ";
        }
        if ($search) {

            $search = addslashes($search);

            $sql .= " AND ( ";
            $sql .= "  {$this->tableName}.user LIKE '%$search%' ";
            $sql .= " ) ";

        }

        return $sql;

    }

    public function getNextRow(int $id): ?array
    {
        return $this->db->where(array(
            'id >' => $id,
            'status' => OrderRefundMassiveStatus::READY
        ))->order_by('id', 'ASC')->limit(1)->get($this->tableName)->row_array();
    }

    public function findByPk(int $id): ?array
    {
        return $this->db->where(array(
            'id' => $id,
        ))->limit(1)->get($this->tableName)->row_array();
    }

    public function setStatus(int $id, string $status, array $errors = []): void
    {
        $item = [
            'status' => $status
        ];
        if ($errors) {
            $item['errors'] = json_encode($errors);
        }
        $this->update($item, $id);
    }

}