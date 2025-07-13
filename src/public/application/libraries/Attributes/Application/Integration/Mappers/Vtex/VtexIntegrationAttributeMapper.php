<?php

namespace libraries\Attributes\Application\Integration\Mappers\Vtex;

require_once APPPATH . "libraries/Attributes/Application/Integration/Mappers/BaseIntegrationAttributeMapper.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";
require_once APPPATH . "libraries/Helpers/StringHandler.php";

use libraries\Attributes\Application\Integration\Mappers\BaseIntegrationAttributeMapper;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Helpers\StringHandler;

class VtexIntegrationAttributeMapper extends BaseIntegrationAttributeMapper
{

    public function mapperIntegrationAttributeValues(array $values): array
    {
        $attrValues = [];
        foreach ($values as $value) {
            if (!$value->IsActive) continue;
            $code = StringHandler::slugify($value->Value, '_');
            if (empty($code)) continue;
            array_push($attrValues, [
                'value' => $value->Value,
                'code' => StringHandler::slugify($value->Value, '_')
            ]);
        }
        return $attrValues;
    }

    public function mapIntegrationFieldType(string $fieldType): string
    {
        return $fieldType == 'list' ? CustomAttribute::FIELD_TYPE_SELECTABLE : (
        $fieldType == 'number' ? CustomAttribute::FIELD_TYPE_NUMBER : CustomAttribute::FIELD_TYPE_STRING
        );
    }
}