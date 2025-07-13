<?php

class Model_settingsreturnchargebackrules extends CI_Model
{
    private $tableName = 'settings_return_chargeback_rules';

    public function __construct()
    {
        parent::__construct();
    }

    public function listIndex(array $postData, bool $onlyCount = false)
    {

        $search = $postData['search']['value'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        if ($onlyCount) {
            $this->db->select("count(DISTINCT {$this->tableName}.id) total");
        } else {
            $this->db->select("{$this->tableName}.*, users.username");
        }
        $this->db->from($this->tableName);
        if ($this->data['usercomp'] != 1){
            $this->db->where($this->tableName.'.active', 1);
        }
        $this->db->join('users', 'users.id = ' . $this->tableName . '.user_id');
        if ($search) {
            $this->db->group_start()->or_like([
                $this->tableName . '.marketplace_int_to' => $search,
                'users.username' => $search
            ])->group_end();
        }

        if ($onlyCount) {
            return $this->db->get()->row_array()['total'];
        }

        $this->db->order_by($orderColumn, $orderColumnDir);
        $this->db->limit($limit, $offset);

        return $this->db->get()->result_array();

    }

    public function getById(int $id): ?array
    {

        $this->db->select("{$this->tableName}.*, users.username");
        $this->db->from($this->tableName);
        $this->db->where($this->tableName.'.id', $id);
        $this->db->join('users', 'users.id = ' . $this->tableName . '.user_id');
        return $this->db->get()->row_array();

    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);
            return $insert == true;
        }
        return false;
    }

    public function marketplaceAlreadyHasRule(string $intTo): bool
    {

        $this->db->select("*");
        $this->db->where('marketplace_int_to', $intTo);
        $this->db->where('active', 1);
        $this->db->from($this->tableName);

        $result = $this->db->get();

        if (!$result->num_rows()) {
            return false;
        }

        return true;

    }

    public function disableItemById(int $id): void
    {

        $this->db->where('id', $id);

        $this->db->update($this->tableName, ['active' => 0]);

    }

}
