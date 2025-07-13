<?php

/**
 * Class Model_webhook_notification_queue
 * @property CI_DB_query_builder $db
 */
class Model_webhook_notification_queue extends CI_Model
{
    private $table = 'webhook_notification_queue';

    public function find($where = []): array
    {
        return $this->db->select(['id'])
                ->from($this->table)
                ->where($where)->get()->row_array() ?? [];
    }

    public function findAllByCriteria($criteria = [], array $groupBy = [], array $orderBy = [], $limit = null): array
    {
        $this->db->query('SET SESSION group_concat_max_len = 500000;');
        $this->db->select([
            '*', 'GROUP_CONCAT(IFNULL(id, NULL) SEPARATOR \',\') AS grouped_ids',
            'GROUP_CONCAT(IFNULL(topic, NULL) SEPARATOR \',\') AS grouped_topics'
        ])->from("{$this->table}  USE INDEX (by_store_topic_status)");
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $this->db->where_in($field, $value);
                unset($criteria[$field]);
            }
        }
        $this->db->where($criteria);
        if (!empty($groupBy)) {
            $this->db->group_by(implode(',', $groupBy));
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $f => $v) {
                $this->db->order_by($f, $v);
            }
        }
        if ($limit !== null) {
            $this->db->limit($limit);
        }
        return $this->db->get()->result_array() ?? [];
    }

    public function save($id = 0, array $data = []): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (empty($id)) {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->table, $data);
        }
        $where = ['id' => $id];
        if (is_array($id)) {
            $ids = implode(',', $id);
            $where = "id IN ({$ids})";
        }
        return $this->db->update($this->table, $data, $where);
    }

    public function remove(array $ids = [], $optional = [])
    {
        $in = implode(',', $ids);
        $where = !empty($ids) ? ["id IN ({$in})" => null] : [];
        $this->db->delete($this->table, array_merge($where, $optional));
    }

    public function getInsertId() {
        return $this->db->insert_id();
    }
}