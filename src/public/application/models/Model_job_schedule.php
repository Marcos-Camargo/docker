<?php

/**
 * Class Model_job_schedule
 * @property CI_DB_query_builder $db
 */
class Model_job_schedule extends CI_Model
{
    const TABLE = 'job_schedule';
    const WAITING_FOR_APPROVAL        = 0; // Aguardando Aprovação
    const IN_PROGRESS                 = 1; // Em andamento
    const FINISHED                    = 2; // Encerrado
    const PREVIOUS_JOB_DID_NOT_FINISH = 3; // Job anterior não encerrou
    const PULLING_FROM_CRONTAB        = 4; // Puxando da Crontab
    const STORE_INACTIVE              = 5; // Loja Inativa
    const IN_QUEUE                    = 6; // Na fila
    const JOB_ERROR                   = 7; // Erro
    const WINDOW_MAINTENANCE          = 8; // Janela de manutenção

    public function __construct()
    {
        parent::__construct();
    }

    public function getInsertId()
    {
        return $this->db->insert_id();
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            return $this->db->update(Model_job_schedule::TABLE, $data);
        }
        return false;
    }

    public function create($data)
    {
        return $this->db->insert(Model_job_schedule::TABLE, $data);
    }
    public function find($where){
        return $this->db->from(Model_job_schedule::TABLE)->where($where)->get()->row_array();
    }

    public function findAll($where){
        return $this->db->from(Model_job_schedule::TABLE)->where($where)->get()->result_array();
    }

    public function delete($id)
    {
        return $this->db->delete(Model_job_schedule::TABLE, ['id' => $id]);
    }
	
	public function getData($id)
    {
	    $sql = "SELECT * FROM job_schedule WHERE id = ?";
	    $query = $this->db->query($sql, array($id));
	    return $query->row_array();
    }

    public function getJobsDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if ($procura != '') {
            $procura = ' WHERE '.substr($procura,5);
        }
        $sql = "SELECT * FROM job_schedule ";

        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getJobsDataCount($procura = '')
    {
        if ($procura != '') {
            $procura = ' WHERE '.substr($procura,5);
        }
        $sql = "SELECT count(*) as qtd FROM job_schedule ".$procura;

        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function runNow($id)
	{
		$sql = "UPDATE job_schedule SET status=?, date_start=NOW(), date_end=null WHERE id = ?";
		return $this->db->query($sql, array(self::WAITING_FOR_APPROVAL, $id));
	}

    public function jobsAlert()
    {

        $sql = "SELECT * FROM job_schedule WHERE status=? AND now() > start_alert";
        $query = $this->db->query($sql, array(self::IN_PROGRESS));
        return $query->result_array();
    }

    public function jobsNotRunning()
    {

        $sql = "SELECT * FROM job_schedule WHERE status=? AND  date_start < date_sub( now(), INTERVAL 1 hour)";
        $query = $this->db->query($sql, array(self::PULLING_FROM_CRONTAB));
        return $query->result_array();
    }

    public function deleteByModuleAndParam(string $module_path, string $params)
    {
        return $this->db->delete(Model_job_schedule::TABLE, ['module_path' => $module_path, 'params' => $params]);
    }

    public function getByModulePath(string $module_path): array
    {
        return $this->db->where('module_path', $module_path)
            ->order_by('id', 'DESC')
            ->get(Model_job_schedule::TABLE)
            ->result_array();
    }

    public function getByModulePathAndStatus(string $module_path, $status): array
    {
        if (!is_array($status)) {
            $status = [$status];
        }

        return $this->db->where('module_path', $module_path)
            ->where_in('status', $status)
            ->get(Model_job_schedule::TABLE)
            ->result_array();
    }

    public function getByStartDateAndEndDate(string $end_date = null): array
    {
        return $this->db->where('date_start <', $end_date)
            ->where('status', self::IN_PROGRESS)
            ->get(self::TABLE)
            ->result_array();
    }

}
