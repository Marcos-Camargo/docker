<?php

class Model_job_integration extends CI_Model
{
    const TABLE = 'job_integration';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        return $this->db->insert(self::TABLE, $data);
    }
    public function find($where)
    {
        return $this->db->from(self::TABLE)->where($where)->get()->row_array();
    }
    public function update($id, $data)
    {
        return $this->db->update(self::TABLE, $data, ['id' => $id]);
    }

    public function setLastRun(?string $integration, int $store, string $job, ?string $date): bool
    {
        $where = array(
            'job'       => $job,
            'store_id'  => $store
        );

        if (!is_null($integration)) {
            $where['LOWER(integration)'] = strtolower($integration);
        }

        $this->db->where($where);

        return (bool)$this->db->update('job_integration', array('last_run' => $date ?? date('Y-m-d H:i:s')));
    }

    /**
     * Recupera a última vez que uma rotina de uma loja, foi executado
     *
     * @param   string      $job    Rotina (job_integration.job)
     * @param   int         $store  Código da loja (stores.id)
     * @return  string|null
     */
    public function getLastRunJob(string $job, int $store): ?string
    {
        $query = $this->db->select('last_run')->get_where('job_integration', array(
            'job'       => $job,
            'store_id'  => $store
        ))->row_array();

        if (!$query || $query['last_run'] === null) {
            return null;
        } else {
            return $query['last_run'];
        }
    }
}
