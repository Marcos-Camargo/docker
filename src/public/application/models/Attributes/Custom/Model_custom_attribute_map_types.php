<?php

namespace models\Attributes\Custom;

use models\Attributes\Custom\Entities\CustomAttributeMapType;
use models\Core\Model\Model;

require_once APPPATH . "models/Core/Model/Model.php";
require_once APPPATH . "models/Attributes/Custom/Entities/CustomAttributeMapType.php";

/**
 * Class Model_custom_attribute_map_types
 * @package models\Attributes\Custom
 */
class Model_custom_attribute_map_types extends Model
{
    protected $table = 'custom_attribute_map_types';

    public function __construct()
    {
        parent::__construct(new CustomAttributeMapType());
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function countCustomAttributesMapByCriteria(array $criteria = []): int
    {
        $this->db->select(['count(attr.id) as numRows'])->from("{$this->table} as attr");
        $this->buildQueryByCriteria($criteria);
        return (int)($this->db->get()->row_array()['numRows'] ?? 0);
    }

    public function fetchCustomAttributesMapByCriteria(array $criteria = [], $offset = 0, $limit = 10): array
    {
        $this->db->select([
            'attr.*', 'custom.name', 'custom.code'
        ])->from("{$this->table} as attr");
        $this->buildQueryByCriteria($criteria);
        $this->db->limit($limit, $offset)->order_by('attr.updated_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function buildQueryByCriteria(array $criteria = [])
    {
        $this->db->join('custom_application_attributes custom', 'attr.custom_attribute_id = custom.id');
        if (!empty($criteria['custom_attribute_id'] ?? '')) {
            $this->db->where('attr.custom_attribute_id', $criteria['custom_attribute_id']);
        }
        if (!empty($criteria['company_id'] ?? '')) {
            $this->db->where('attr.company_id', $criteria['company_id']);
        }
        if (!empty($criteria['store_id'] ?? '')) {
            $this->db->where('attr.store_id', $criteria['store_id']);
        } elseif (is_array($criteria['stores'] ?? []) && !empty($criteria['stores'] ?? '')) {
            $this->db->where_in('attr.store_id', $criteria['stores']);
        }
        if (!empty($criteria['search'] ?? '')) {
            $this->db->where('attr.value LIKE', "%{$criteria['search']}%");
        }
        if (!empty($criteria['value'] ?? '')) {
            $this->db->where('attr.value', "{$criteria['value']}");
        }
        if (($criteria['status'] ?? -1) >= 0) {
            $this->db->where('custom.status', $criteria['status']);
        }
        if (($criteria['enabled'] ?? -1) >= 0) {
            $this->db->where('attr.enabled', $criteria['enabled']);
        }
        if (!empty($criteria['module'] ?? '')) {
            $this->db->where('custom.module', "{$criteria['module']}");
        }
    }
}