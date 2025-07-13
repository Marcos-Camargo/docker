<?php

class Model_log_count_simulation_daily extends CI_Model
{
    const TABLE = "log_count_simulation_daily";

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data): bool
    {
        return $this->db->insert($this::TABLE, $data);

    }

    public function update($id, $data): bool
    {
        return $this->db->update($this::TABLE, $data, ['id' => $id]);
    }

    public function updateCountByData(int $count_request, array $where, array $create): bool
    {
        $data = $this->db->get_where($this::TABLE, $where)->row_array();

        // Se não existe o registro será criado.
        if (!$data) {
            return $this->create($create);
        }

        $count_request = $data['count_request'] + $count_request;

        return $this->db->update($this::TABLE, array('count_request' => $count_request), $where);
    }
}
