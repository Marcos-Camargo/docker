<?php

namespace libraries\Attributes\Custom;

use models\Attributes\Custom\Model_custom_application_attribute_values;
use models\Attributes\Custom\Model_custom_application_attributes;
use models\Core\Entity\Entity;

require_once APPPATH . "models/Attributes/Custom/Model_custom_application_attributes.php";
require_once APPPATH . "models/Attributes/Custom/Model_custom_application_attribute_values.php";

/**
 * Class CustomAttributeService
 * @package libraries\Attributes\Custom
 * @property Model_custom_application_attributes $customAppAttrModel
 * @property Model_custom_application_attribute_values $customAppAttrValueModel
 */
class CustomAttributeService
{

    protected $customAppAttrModel;
    protected $customAppAttrValueModel;

    public function __construct()
    {
        $this->customAppAttrModel = new Model_custom_application_attributes();
        $this->customAppAttrValueModel = new Model_custom_application_attribute_values();
    }


    public function createUpdateAccountAttributes($appAttributes, $integration)
    {
        $customAttributes = [];
        foreach ($appAttributes as $appAttribute) {
            $customAttr = [
                'store_id' => $integration['store_id'],
                'company_id' => $integration['company_id'],
                'attribute_id' => $appAttribute['id'] ?? 0,
                'category_id' => $appAttribute['category_id'] ?? 0,
                'status' => ($appAttribute['active'] ?? 1) == 1 ? 1 : 0,
                'required' => $appAttribute['required'] ?? 0,
                'name' => $appAttribute['name'],
                'code' => $appAttribute['code'],
                'module' => $appAttribute['module'],
                'field_type' => $appAttribute['field_type'] ?? (!empty($appAttribute['values'] ?? []) ? 'selectable' : 'custom')
            ];
            try {
                $attrEntity = $this->saveCustomAttribute($customAttr);
                if (isset($appAttribute['values'])) {
                    foreach ($appAttribute['values'] as $attrValue) {
                        $customAttrValue = [
                            'store_id' => $integration['store_id'],
                            'company_id' => $integration['company_id'],
                            'attribute_value_id' => $attrValue['id'] ?? 0,
                            'custom_application_attribute_id' => $attrEntity->getValueByColumn('id'),
                            'value' => $attrValue['value'],
                            'code' => $attrValue['code'],
                            'enabled' => $attrValue['enabled'] ?? 1,
                            'visible' => $attrValue['visible'] ?? 1,
                        ];
                        $attrValueEntity = $this->saveCustomAttributeValue($customAttrValue);
                        $customAttr['values'][] = array_merge($customAttrValue, ['id' => $attrValueEntity->getValueByColumn('id')]);
                    }
                }
                array_push($customAttributes, array_merge($customAttr, ['id' => $attrEntity->getValueByColumn('id')]));
            } catch (\Throwable $e) {
                echo $e->getMessage();
            }
        }
        return $customAttributes;
    }

    public function saveCustomAttribute($attribute = [], $id = 0)
    {
        $attributeEntity = $this->customAppAttrModel->findOneWhere(array_merge([
                'company_id' => $attribute['company_id'],
                'store_id' => $attribute['store_id'],
                'code' => $attribute['code'],
                'module' => $attribute['module'],
            ], (
            !empty($attribute['category_id'] ?? 0) ? [
                'category_id' => $attribute['category_id']
            ] : []))
        );
        if ($attributeEntity->exists() && $attributeEntity->getValueByColumn('id') != $id) {
            $id = $attributeEntity->getValueByColumn('id');
            $attribute['id'] = $id;
        } elseif ($id > 0) {
            $attributeEntity = $this->customAppAttrModel->findOneWhere([
                'id' => $id,
                'company_id' => $attribute['company_id'],
                'store_id' => $attribute['store_id']
            ]);
            if (!$attributeEntity->exists()) {
                throw new \Exception('Não existe um atributo a ser atualizado com o ID informado');
            }
            $attribute['id'] = $id;
        }

        $attributeEntity = $this->customAppAttrModel->save($attribute, $attribute['id'] ?? null);

        if (isset($attribute['values'])) {
            foreach ($attribute['values'] as $attrValue) {
                $attrValue = array_merge($attrValue, [
                    'custom_application_attribute_id' => $attributeEntity->getValueByColumn('id'),
                    'company_id' => $attribute['company_id'],
                    'store_id' => $attribute['store_id']
                ]);
                try {
                    $this->saveCustomAttributeValue($attrValue);
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }
        return $attributeEntity;
    }

    public function saveCustomAttributeValue($attrValue)
    {
        $attributeValueEntity = $this->customAppAttrValueModel->findOneWhere([
            'custom_application_attribute_id' => $attrValue['custom_application_attribute_id'],
            'company_id' => $attrValue['company_id'],
            'store_id' => $attrValue['store_id'],
            'code' => $attrValue['code'],
        ]);
        if ($attributeValueEntity->exists()) {
            $attrValue['id'] = $attributeValueEntity->getValueByColumn('id');
        }
        return $this->customAppAttrValueModel->save($attrValue, $attrValue['id'] ?? null);
    }

    /**
     * @param array $params
     * @return Entity[]
     */
    public function getCustomAttributes(array $params = [])
    {
        $customAttrs = $this->customAppAttrModel
            ->orderBy(['required' => 'DESC'])
            ->findAllWhere($params);
        foreach ($customAttrs as $k => $customAttr) {
            $customAttrValues = $this->customAppAttrValueModel
                ->orderBy(['value' => 'ASC'])
                ->findAllWhere([
                    'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                    'enabled' => 1,
                    'visible' => 1,
                ]);
            if (!empty($customAttrValues)) {
                $customAttr->{'values'} = $customAttrValues;
                $customAttrs[$k] = $customAttr;
            }
        }
        return $customAttrs;
    }

    public function getCustomAttributeById(array $customAttr, array $customAttrValues = ['visible' => 1])
    {
        $customAttr = $this->customAppAttrModel->findOneWhere([
            'id' => $customAttr['id'],
            'company_id' => $customAttr['company_id'],
            'store_id' => $customAttr['store_id'],
        ]);
        $customAttrValues = $this->customAppAttrValueModel->findAllWhere(array_merge([
            'custom_application_attribute_id' => $customAttr->getValueByColumn('id')
        ], $customAttrValues));
        if (!empty($customAttrValues)) {
            $customAttr->{'values'} = $customAttrValues;
        }
        return $customAttr;
    }

    public function getCustomAttributeByCriteria(array $criteria): Entity
    {
        return $this->customAppAttrModel->findOneWhere($criteria);
    }

    public function getCustomAttributeValueByCriteria(array $criteria): Entity
    {
        return $this->customAppAttrValueModel->findOneWhere($criteria);
    }

    public function countCustomVariationsByCriteria(array $criteria): int
    {
        return $this->customAppAttrModel->countCustomVariationsByCriteria($criteria);
    }

    public function fetchCustomVariationsByCriteria(array $criteria, $offset = 0, $limit = 10): array
    {
        return $this->customAppAttrModel->fetchCustomVariationsByCriteria($criteria, $offset, $limit);
    }

}