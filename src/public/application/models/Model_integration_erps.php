<?php

/**
 * Class Model_integration_erps
 * @property CI_DB_query_builder $db
 */
class Model_integration_erps extends CI_Model
{
    private $table = 'integration_erps';
    public $type = array(
        "backoffice"        => 1,
        "external"          => 2,
        "external_logistic" => 3
    );

    public function getById(int $id): ?object
    {
        return $this->db->where('id', $id)->get('integration_erps')->row_object();
    }

    public function getByName(string $name): ?array
    {
        return $this->db->where('name', $name)->get('integration_erps')->row_array();
    }

    public function getListIntegrations(array $filters, int $offset = 0, int $limit = 10, $returnCount = false)
    {
        $this->db->select('id, name, description, type, active');

        foreach ($filters as $type => $filter) {
            switch ($type) {
                case 'like':
                    foreach ($filter as $k_ => $f_) {
                        $this->db->like($k_, $f_);
                    }
                    break;
                case 'order':
                    foreach ($filter as $k_ => $f_) {
                        $this->db->order_by($k_, $f_);
                    }
                    break;
                case 'where':
                    foreach ($filter as $k_ => $f_) {
                        $this->db->where($k_, $f_);
                    }
                    break;
            }
        }

        if (count($filters)) {
            $this->db->limit($limit, $offset);
        }

        if ($returnCount) {
            return $this->db->count_all_results('integration_erps');
        }

        return $this->db->get('integration_erps')->result_object();
    }

    public function updateById(array $data, int $id): bool
    {
        return $this->db->where('id', $id)->update('integration_erps', $data);
    }

    /**
     * @param   array       $data   Dados para criação.
     * @return  int|false
     */
    public function create(array $data)
    {
        $insert = $this->db->insert('integration_erps', $data);
        return $insert ? $this->db->insert_id() : false;
    }

    public function getIntegrationActive(int $type = null): array
    {
        $this->db->where('active', true);

        if (!is_null($type)) {
            $this->db->where('type', $type);
        }

        return $this->db->order_by('description')->get('integration_erps')->result_object();
    }

    public function find($where = []): array
    {
        return $this->db->select(['*'])
            ->from($this->table)
            ->where($where)->get()->row_array() ?? [];
    }

    public function getByApplicationId(string $applicationId): array
    {
        return $this->db->select(['*'])
            ->from($this->table)
            ->where(['hash' => $applicationId])
            ->get()->row_array() ?? [];
    }

    public function save(int $id = 0, array $data = []): bool
    {
        $data['date_updated'] = date('Y-m-d H:i:s');
        if (empty($id)) {
            $data['date_created'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->table, $data);
        }
        $where = ['id' => $id];
        return $this->db->update($this->table, $data, $where);
    }

    public function getInsertId(): int
    {
        return $this->db->insert_id();
    }

    public static function isActive($integrationErp): bool
    {
        return ((int)($integrationErp['active'] ?? 0)) === 1;
    }
}
