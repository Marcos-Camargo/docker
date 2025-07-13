<?php

namespace libraries\Attributes\Custom;

use models\Attributes\Custom\Model_custom_attribute_map_types;
use models\Core\Entity\Entity;

require_once APPPATH . "models/Attributes/Custom/Model_custom_attribute_map_types.php";

/**
 * Class CustomAttributeService
 * @package libraries\Attributes\Custom
 * @property Model_custom_attribute_map_types $customAttrMapModel
 */
class CustomAttributeMapService
{

    public function __construct()
    {
        $this->customAttrMapModel = new Model_custom_attribute_map_types();
    }


    public function createUpdateAttributesMapping($mapAttributes)
    {
        $customAttributes = [];
        foreach ($mapAttributes as $mapAttribute) {
            $customAttr = [
                'id' => $mapAttribute['id'] ?? null,
                'company_id' => $mapAttribute['company_id'],
                'store_id' => $mapAttribute['store_id'],
                'custom_attribute_id' => $mapAttribute['custom_attribute_id'] ?? 0,
                'enabled' => ($mapAttribute['enabled'] ?? 1) == 1 ? 1 : 0,
                'visible' => ($mapAttribute['visible'] ?? 1) == 1 ? 1 : 0,
                'value' => $mapAttribute['value']
            ];
            try {
                $attrEntity = $this->saveCustomAttribute($customAttr);
                $customAttributes[] = array_merge($customAttr, ['id' => $attrEntity->getValueByColumn('id')]);
            } catch (\Throwable $e) {

            }
        }
        return $customAttributes;
    }

    public function saveCustomAttribute($attribute = [])
    {
        if (($attribute['id'] ?? 0) > 0) {
            $attributeEntity = $this->customAttrMapModel->findOneWhere([
                'id' => $attribute['id'],
                'company_id' => $attribute['company_id'],
                'store_id' => $attribute['store_id']
            ]);
            if (!$attributeEntity->exists()) {
                throw new \Exception("Não existe um mapeamento de ID {$attribute['id']} para ser atualizado.");
            }
        }

        return $this->customAttrMapModel->save($attribute, $attribute['id'] ?? null);
    }


    public function getCustomAttributeByCriteria(array $criteria): Entity
    {
        return $this->customAttrMapModel->findOneWhere($criteria);
    }

    public function countCustomAttributesMapByCriteria(array $criteria): int
    {
        return $this->customAttrMapModel->countCustomAttributesMapByCriteria($criteria);
    }

    public function fetchCustomAttributesMapByCriteria(array $criteria, $offset = 0, $limit = 10): array
    {
        return $this->customAttrMapModel->fetchCustomAttributesMapByCriteria($criteria, $offset, $limit);
    }

    public function removeCustomAttrMapById($mappedAttrId, $params): bool
    {
        try {
            return $this->customAttrMapModel->delete($mappedAttrId, $params);
        } catch (\Throwable $e) {
            return false;
        }
    }
}