<?php

namespace libraries\Attributes\Integration;

use models\Attributes\Custom\Model_custom_application_attribute_values;
use models\Attributes\Custom\Model_custom_application_attributes;
use models\Attributes\Integration\Model_integration_custom_application_attribute_values;
use models\Attributes\Integration\Model_integration_custom_application_attributes;

require_once APPPATH . "models/Attributes/Custom/Model_custom_application_attributes.php";
require_once APPPATH . "models/Attributes/Custom/Model_custom_application_attribute_values.php";
require_once APPPATH . "models/Attributes/Integration/Model_integration_custom_application_attributes.php";
require_once APPPATH . "models/Attributes/Integration/Model_integration_custom_application_attribute_values.php";

/**
 * Class IntegrationCustomAttributeService
 * @package libraries\Attributes\Integration
 * @property Model_custom_application_attributes $customAppAttrModel
 * @property Model_custom_application_attribute_values $customAppAttrValueModel
 * @property Model_integration_custom_application_attributes $integrationAppAttrModel
 * @property Model_integration_custom_application_attribute_values $integrationAppAttrValueModel
 */
class IntegrationCustomAttributeService
{

    protected $customAppAttrModel;
    protected $customAppAttrValueModel;
    protected $integrationAppAttrModel;
    protected $integrationAppAttrValueModel;

    public function __construct()
    {
        $this->customAppAttrModel = new Model_custom_application_attributes();
        $this->customAppAttrValueModel = new Model_custom_application_attribute_values();
        $this->integrationAppAttrModel = new Model_integration_custom_application_attributes();
        $this->integrationAppAttrValueModel = new Model_integration_custom_application_attribute_values();
    }

    public function saveIntegrationCustomAttribute($attribute)
    {
        $customAttr = $this->customAppAttrModel->findOneWhere([
            'company_id' => $attribute['company_id'],
            'store_id' => $attribute['store_id'],
            'code' => $attribute['code'],
            'module' => $attribute['module'],
        ]);
        if (!$customAttr->exists()) {
            throw new \Exception("Não encontramos o atributo '{$attribute['code']}' para o módulo '{$attribute['module']}'.");
        }
        $integrationAttr = $this->integrationAppAttrModel->findOneWhere([
                'company_id' => $customAttr->getValueByColumn('company_id'),
                'store_id' => $customAttr->getValueByColumn('store_id'),
                'integration_id' => $attribute['integration_id'],
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'module' => $customAttr->getValueByColumn('module')
            ] + (
            $attribute['is_variation_attribute'] ? ['is_variation_attribute' => $attribute['is_variation_attribute']] : []
            )
        );

        $integrationCustomAttr = [];
        if ($integrationAttr->exists()) {
            $integrationCustomAttr['id'] = $integrationAttr->getValueByColumn('id');
        }

        $integrationCustomAttr['company_id'] = $customAttr->getValueByColumn('company_id');
        $integrationCustomAttr['store_id'] = $customAttr->getValueByColumn('store_id');
        $integrationCustomAttr['integration_id'] = $attribute['integration_id'];
        $integrationCustomAttr['custom_application_attribute_id'] = $customAttr->getValueByColumn('id');
        $integrationCustomAttr['required'] = $attribute['required'] ?? $integrationAttr->getValueByColumn('required') ?? 0;
        $integrationCustomAttr['field_type'] = $attribute['field_type'] ?? $integrationAttr->getValueByColumn('field_type') ?? $customAttr->getValueByColumn('field_type');;
        $integrationCustomAttr['is_variation_attribute'] = $attribute['is_variation_attribute'] ?? $integrationAttr->getValueByColumn('is_variation_attribute') ?? 0;
        $integrationCustomAttr['module'] = $attribute['module'] ?? $integrationAttr->getValueByColumn('module') ?? $customAttr->getValueByColumn('module');
        $integrationCustomAttr['integration_external_attribute_id'] = $attribute['external_id'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_id') ?? $attribute['code'];
        $integrationCustomAttr['integration_external_attribute_code'] = $attribute['external_code'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_code') ?? $attribute['code'];
        $integrationCustomAttr['integration_external_attribute_value'] = $attribute['external_value'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_value') ?? $attribute['code'];

        $integrationCustomAttrEntity = $this->integrationAppAttrModel->save($integrationCustomAttr, $integrationCustomAttr['id'] ?? null);
        if (!$integrationCustomAttrEntity->exists()) {
            throw new \Exception("Ocorreu um erro ao vincular o atributo '{$attribute['code']}'.");
        }
        foreach ($attribute['values'] as $value) {
            $customAttrValue = $this->customAppAttrValueModel->findOneWhere([
                'company_id' => $customAttr->getValueByColumn('company_id'),
                'store_id' => $customAttr->getValueByColumn('store_id'),
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'code' => $value['code'],
            ]);
            if (!$customAttrValue->exists()) {
                if (strcasecmp($value['code'], 'default') === 0) continue;
                throw new \Exception("Não foi encontrado o valor '{$value['code']}' para o atributo '{$attribute['code']}' do módulo '{$attribute['module']}'.");
            }

            $integrationAttrValue = $this->integrationAppAttrValueModel->findOneWhere([
                'company_id' => $customAttrValue->getValueByColumn('company_id'),
                'store_id' => $customAttrValue->getValueByColumn('store_id'),
                'integration_id' => $integrationCustomAttrEntity->getValueByColumn('integration_id') ?? $attribute['integration_id'],
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'custom_application_attribute_value_id' => $customAttrValue->getValueByColumn('id'),
            ]);

            if ($integrationAttrValue->exists()) {
                if ($integrationAttrValue->getValueByColumn('integration_external_attribute_value_id') != $value['external_id']) {
                    /*throw new \Exception(
                        "Já existe um vinculo no atributo '{$customAttr->getValueByColumn('name')}' para o valor '{$customAttrValue->getValueByColumn('value')}'."
                    );*/
                }
            }

            $integrationAttrValue = $this->integrationAppAttrValueModel->findOneWhere([
                'company_id' => $customAttrValue->getValueByColumn('company_id'),
                'store_id' => $customAttrValue->getValueByColumn('store_id'),
                'integration_id' => $integrationCustomAttrEntity->getValueByColumn('integration_id') ?? $attribute['integration_id'],
                'integration_external_attribute_value_id' => $value['external_id']
            ]);
            if ($integrationAttrValue->exists()) {
                if ($integrationAttrValue->getValueByColumn('integration_custom_application_attribute_id')
                    != $integrationCustomAttrEntity->getValueByColumn('id')) {
                    $checkCustomAttr = $this->customAppAttrModel->findOneWhere([
                        'id' => $integrationAttrValue->getValueByColumn('custom_application_attribute_id'),
                        'company_id' => $integrationAttrValue->getValueByColumn('company_id'),
                        'store_id' => $integrationAttrValue->getValueByColumn('store_id')
                    ]);
                    /*throw new \Exception(
                        "Já existe um vinculo no atributo '{$checkCustomAttr->getValueByColumn('name')}' com o valor de '{$value['external_id']}'."
                    );*/
                }
                if ($integrationAttrValue->getValueByColumn('custom_application_attribute_value_id')
                    != $customAttrValue->getValueByColumn('id')) {
                    $this->removeIntegrationAttributeValuesByParams([
                        'company_id' => $integrationAttrValue->getValueByColumn('company_id'),
                        'store_id' => $integrationAttrValue->getValueByColumn('store_id'),
                        'integration_id' => $integrationAttrValue->getValueByColumn('integration_id') ?? $attribute['integration_id'],
                        'external_id' => $integrationAttrValue->getValueByColumn('integration_external_attribute_value_id')
                    ]);
                    /*throw new \Exception(
                        "Já existe um vinculo no atributo '{$customAttr->getValueByColumn('name')}' com o valor de '{$value['external_id']}'."
                    );*/
                }
            }

            $integrationAttrValue = $this->integrationAppAttrValueModel->findOneWhere([
                'company_id' => $customAttrValue->getValueByColumn('company_id'),
                'store_id' => $customAttrValue->getValueByColumn('store_id'),
                'integration_id' => $integrationCustomAttrEntity->getValueByColumn('integration_id') ?? $attribute['integration_id'],
                'integration_custom_application_attribute_id' => $integrationCustomAttrEntity->getValueByColumn('id'),
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'custom_application_attribute_value_id' => $customAttrValue->getValueByColumn('id'),
                'integration_external_attribute_value_id' => $value['external_id']
            ]);
            $integrationCustomAttrValue = [];
            if ($integrationAttrValue->exists()) {
                $integrationCustomAttrValue['id'] = $integrationAttrValue->getValueByColumn('id');
            }

            $integrationCustomAttrValue['company_id'] = $customAttrValue->getValueByColumn('company_id');
            $integrationCustomAttrValue['store_id'] = $customAttrValue->getValueByColumn('store_id');
            $integrationCustomAttrValue['integration_id'] = $integrationAttrValue->getValueByColumn('integration_id') ?? $integrationCustomAttrEntity->getValueByColumn('integration_id') ?? $attribute['integration_id'];
            $integrationCustomAttrValue['integration_custom_application_attribute_id'] = $integrationCustomAttrEntity->getValueByColumn('id');
            $integrationCustomAttrValue['custom_application_attribute_id'] = $customAttr->getValueByColumn('id');
            $integrationCustomAttrValue['custom_application_attribute_value_id'] = $customAttrValue->getValueByColumn('id');
            $integrationCustomAttrValue['integration_external_attribute_value_id'] = $value['external_id'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_value_id') ?? $value['code'];
            $integrationCustomAttrValue['integration_external_attribute_value_code'] = $value['external_code'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_value_code') ?? $value['code'];
            $integrationCustomAttrValue['integration_external_attribute_value_value'] = $value['external_value'] ?? $integrationAttr->getValueByColumn('integration_external_attribute_value_value') ?? $value['code'];

            try {
                $integrationCustomAttrValueEntity = $this->integrationAppAttrValueModel->save($integrationCustomAttrValue, $integrationCustomAttrValue['id'] ?? null);
                if ($integrationCustomAttrValueEntity->exists()) {
                    $integrationCustomAttrEntity->{'values'}[] = $integrationCustomAttrValueEntity;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return $integrationCustomAttrEntity;
    }

    public function getIntegrationAttributeValueByExternalId($attributeValue)
    {
        return $this->integrationAppAttrValueModel->findOneWhere([
            'company_id' => $attributeValue['company_id'],
            'store_id' => $attributeValue['store_id'],
            'integration_id' => $attributeValue['integration_id'],
            'integration_external_attribute_value_id' => $attributeValue['external_id'],
        ]);
    }

    public function getAllIntegrationAttributeValueByExternalId($attributeValue)
    {
        return $this->integrationAppAttrValueModel->findAllWhere([
            'company_id' => $attributeValue['company_id'],
            'store_id' => $attributeValue['store_id'],
            'integration_id' => $attributeValue['integration_id'],
            'integration_external_attribute_value_id' => $attributeValue['external_id'],
        ]);
    }

    public function removeIntegrationAttributeValuesByParams($params)
    {
        $intAttrValues = $this->getAllIntegrationAttributeValueByExternalId([
            'company_id' => $params['company_id'],
            'store_id' => $params['store_id'],
            'integration_id' => $params['integration_id'],
            'external_id' => $params['external_id'],
        ]);
        $deleted = true;
        foreach ($intAttrValues as $intAttrValue) {
            if ($intAttrValue->exists()) {
                $deleted = $this->removeIntegrationAttributeValueById($intAttrValue->getValueByColumn('id'));
                $deleted = $deleted ? true : $deleted;
            }
        }
        return $deleted;
    }

    public function removeIntegrationAttributeValueById($attributeValueId)
    {
        return $this->integrationAppAttrValueModel->delete($attributeValueId);
    }

}