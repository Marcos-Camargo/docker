<?php

namespace models\Core\Model;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

load_class('Model', 'core');

/**
 * Class Model
 * @package models\Core\Model
 * @property Entity $entity
 * @property \CI_DB_query_builder $db
 */
abstract class Model extends \CI_Model
{
    protected $table = '';
    protected $primaryKey = 'id';

    protected $entity;

    protected $orderBy = [];

    public function __construct(Entity $entity = null)
    {
        parent::__construct();
        $this->entity = $entity;
    }

    public function save($data, $id = null): Entity
    {
        try {
            $this->db->trans_begin();
            if (empty($id)) {
                $record = $this->entity->assignEntityColumnsValuesToCreate($data);
                $saved = $this->db->insert($this->getTableName(), $record);
                if (!$saved) {
                    throw new \Exception('Error on insert record.');
                }
                $this->entity->setValueByColumn($this->primaryKey, $this->db->insert_id());
            } else {
                $record = $this->entity->assignEntityColumnsValuesToUpdate($data);
                $saved = $this->db->update($this->getTableName(), $record, [
                    "{$this->primaryKey}" => $id
                ]);
                if (!$saved) {
                    throw new \Exception("Error on update record.");
                }
            }
            $this->db->trans_commit();
            return $this->entity;
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function delete($id, $optional = [])
    {
        $optional = $this->entity->mapColumns($optional);
        try {
            $this->db->trans_begin();
            $deleted = $this->db->delete($this->getTableName(), ['id' => $id] + $optional);
            $this->db->trans_commit();
            return $deleted;
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public abstract function getTableName(): string;

    public function findOneWhere($where = []): Entity
    {
        $where = is_array($where) ? $this->entity->mapColumns($where) : $where;
        $record = $this->db->select('*')
            ->from($this->getTableName())
            ->where($where)->limit(1)
            ->get()->row_array();
        $this->entity->assignEntityColumnsValues($record);
        return $this->entity;
    }

    /**
     * @param array $where
     * @return Entity[]
     */
    public function findAllWhere(array $where = []): array
    {
        $where = is_array($where) ? $this->entity->mapColumns($where) : $where;
        $this->db->select('*')
            ->from($this->getTableName())
            ->where($where);
        $this->applyOrderBy();
        $records = $this->db->get()->result_array();
        if (empty($records)) {
            return [];
        }
        return array_map(function ($item) {
            $entity = new $this->entity();
            $entity->assignEntityColumnsValues($item);
            return $entity;
        }, $records);
    }

    public function orderBy(array $orderBy): Model
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    protected function applyOrderBy()
    {
        foreach ($this->orderBy ?? [] as $column => $direction) {
            $this->db->order_by($column, $direction);
        }
    }

}