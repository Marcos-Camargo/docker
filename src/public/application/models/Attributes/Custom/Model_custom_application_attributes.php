<?php

namespace models\Attributes\Custom;

use models\Attributes\Custom\Entities\CustomApplicationAttribute;
use models\Core\Model\Model;

require_once APPPATH . "models/Core/Model/Model.php";
require_once APPPATH . "models/Attributes/Custom/Entities/CustomApplicationAttribute.php";

/**
 * Class Model_custom_application_attributes
 * @package models\Attributes\Custom
 */
class Model_custom_application_attributes extends Model
{
    protected $table = 'custom_application_attributes';

    public function __construct()
    {
        parent::__construct(new CustomApplicationAttribute());
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function countCustomVariationsByCriteria(array $criteria = []): int
    {
        $this->db->select(['count(attr.id) as numRows'])->from("{$this->table} as attr");
        $this->buildQueryByCriteria($criteria);
        return (int)($this->db->get()->row_array()['numRows'] ?? 0);
    }

    public function fetchCustomVariationsByCriteria(array $criteria = [], $offset = 0, $limit = 10): array
    {
        $this->db->select([
            'attr.*', 's.name as store_name'
        ])->from("{$this->table} as attr");
        $this->buildQueryByCriteria($criteria);
        $this->db->limit($limit, $offset);
        $this->db->group_by('attr.id');
        return $this->db->get()->result_array();
    }

    public function buildQueryByCriteria(array $criteria = [])
    {
        $this->db->join('stores s', 'attr.store_id = s.id');
        if (!empty($criteria['company_id'] ?? '')) {
            $this->db->where('attr.company_id', $criteria['company_id']);
        }
        if (!empty($criteria['store_id'] ?? '')) {
            $this->db->where('attr.store_id', $criteria['store_id']);
        } elseif (is_array($criteria['stores'] ?? []) && !empty($criteria['stores'] ?? '')) {
            $this->db->where_in('attr.store_id', $criteria['stores']);
        }
        if (!empty($criteria['search'] ?? '')) {
            $this->db->where([$this->db->compile_binds("(attr.name LIKE ? OR s.name LIKE ?)", ["%{$criteria['search']}%", "%{$criteria['search']}%"]) => null]);
        }

        if (!empty($criteria['module'] ?? '')) {
            $this->db->where('attr.module', $criteria['module']);
        }
    }
}