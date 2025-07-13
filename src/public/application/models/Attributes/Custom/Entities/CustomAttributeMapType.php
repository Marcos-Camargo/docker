<?php

namespace models\Attributes\Custom\Entities;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

class CustomAttributeMapType extends Entity
{
    public $id;
    public $custom_attribute_id;
    public $company_id;
    public $store_id;
    public $enabled;
    public $visible;
    public $value;
    public $created_at = null;
    public $updated_at = null;

    public function assignEntityColumnsValuesToCreate($data): array
    {
        return array_merge(parent::assignEntityColumnsValuesToCreate($data), [
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function assignEntityColumnsValuesToUpdate($data): array
    {
        return array_merge(parent::assignEntityColumnsValuesToUpdate($data), [
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}