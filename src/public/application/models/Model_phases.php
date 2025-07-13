<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para LOjas/Depositos
 
 */

class Model_phases extends CI_Model
{
    const TABLE = 'phases p';
    const TABLE_SIMPLE = 'phases';

    public function findStageById($id)
    {
        if ($id) {
            return $this->db->select()->from(self::TABLE)->where(['id' => $id])->get()->row_array();
        }
        return false;
    }
    public function getAll()
    {
        return $this->db->select(['name', 'id'])->from(self::TABLE)->get()->result_array();
    }
    public function getAllWithReposable($where = [], $limit = 200, $orderby = 's.id', $direction = 'asc',  $phase = '', $responsable = "", $status = [], $search = '', $offset = 0)
    {
        $query = $this->db->select([
            'p.name',
            'p.id',
            'p.responsable_id',
            'p.status',
            "CONCAT(users.firstname,' ',users.lastname) as user_name"
        ])->from(self::TABLE)
            ->join('users', 'users.id=p.responsable_id')
            ->where($where)->order_by($orderby, $direction)->limit($limit)->offset($offset);
        $query = $query->where_in("users.id", $responsable);
        $query = $query->where_in("p.id", $phase);
        if (!empty($status)) {
            $query = $query->where_in("p.status", $status);
        }
        if (!empty($search)) {
            $query = $query->group_start()->or_like([
                "CONCAT(users.firstname,' ',users.lastname)" => $search,
                'p.name' => $search
            ])->group_end();
        }
        return $query->get()->result_array();
    }
    public function countAll()
    {
        return $this->db->select()->from(self::TABLE)->count_all_results();
    }
    public function countFromWhere($where, $phase = '', $responsable = "", $status = [], $search = '', $limit = 200, $offset = 0)
    {
        $query = $this->db->select(
            [
                'p.name',
                'p.id',
                'p.responsable_id',
                'p.status',
                "CONCAT(users.firstname,' ',users.lastname) as user_name"
            ]
        )->from(self::TABLE)->where($where, $limit, $offset)
            ->join('users', 'users.id=p.responsable_id');
        $query = $query->where_in("p.responsable_id", $responsable);
        $query = $query->where_in("p.id", $phase);
        if (!empty($status)) {
            $query = $query->where_in("p.status", $status);
        }
        if (!empty($search)) {
            $query = $query->group_start()->or_like([
                "CONCAT(users.firstname,' ',users.lastname)" => $search,
                'p.name' => $search
            ])->group_end();
        }
        return $query->count_all_results();
    }
    public function existPhaseByName($name)
    {
        return $this->db->select()->from(self::TABLE)->where(['name' => $name])->count_all_results() != 0;
    }
    public function create($phase)
    {
        return $this->db->insert(self::TABLE_SIMPLE, $phase);
    }
    public function update($phase_id, $phase)
    {
        return $this->db->update(self::TABLE_SIMPLE, $phase, ['id' => $phase_id]);
    }
    public function getPhaseByNameOrId($id, $name)
    {
        $where_data = ['id' => $id, 'name' => $name];
        return $this->db->select()->from(self::TABLE)->or_where($where_data)->get()->row_array();
    }
    public function getPhaseByStore_id($store_id)
    {
        return $this->db->select('p.*')->from('stores s')->where(['s.id' => $store_id])->join('phases p', 'p.id=s.phase_id')->get()->row_array();
    }
    public function getActivePhases()
    {
        return  $this->db->select()->from(self::TABLE)->where(['p.status' => 1])->get()->result_array();
    }
}
