<?php

namespace libraries\Attributes\Application;

/**
 * Class ApplicationAttributeService
 * @package libraries\Attributes\Application
 * @property \Model_attributes $attributesModel;
 * @property \Model_settings $settingsModel;
 */
class ApplicationAttributeService
{

    public function __construct(\Model_attributes $attributesModel, \Model_settings $settingsModel)
    {
        $this->attributesModel = $attributesModel;
        $this->settingsModel = $settingsModel;
    }

    public function getAttributesDefinitionsByModule(string $module = null): array
    {
        $settingsModel = $this->settingsModel;
        if (file_exists(dirname(__FILE__) . "/data/{$module}.attributes.php"))
            return include dirname(__FILE__) . "/data/{$module}.attributes.php";
        return [];
    }

    public function createUpdateApplicationAttributes($attributes)
    {
        foreach ($attributes as $a => $attribute) {
            $attribute = $this->attributesModel->saveAttribute($attribute);
            if (isset($attribute['values']) && is_array($attribute['values'])) {
                foreach ($attribute['values'] as $v => $value) {
                    $value['attribute_id'] = $attribute['id'];
                    $value = $this->attributesModel->saveAttributeValue($value);
                    $attribute['values'][$v] = $value;
                }
            }
            $attributes[$a] = $attribute;
        }
        return $attributes;
    }

    public function getAttributesApplicationByModule(string $module = null): array
    {
        $localAttributes = $this->attributesModel->getAttributes([
            'att_type' => $module
        ]);
        $attrsApp = [];
        foreach ($localAttributes as $localAttribute) {
            $attrApp = [
                'id' => $localAttribute['id'],
                'name' => $localAttribute['name'],
                'code' => $localAttribute['code'],
                'module' => $localAttribute['att_type'],
                'active' => $localAttribute['active'],
                'system' => $localAttribute['system'],
                'visible' => $localAttribute['visible']
            ];

            $values = $this->attributesModel->getAttributeValueData($localAttribute['id']);
            if (!empty($values)) {
                foreach ($values as $value) {
                    $attrValueApp = [
                        'id' => $value['id'],
                        'attribute_id' => $value['attribute_parent_id'],
                        'value' => $value['value'],
                        'code' => $value['code'],
                        'enabled' => $value['enabled'] ?? \Model_attributes::ACTIVE,
                        'visible' => $value['visible'] ?? \Model_attributes::VISIBLE,
                    ];
                    $attrApp['values'][] = $attrValueApp;
                }
            }
            array_push($attrsApp, $attrApp);
        }
        return $attrsApp;
    }

}