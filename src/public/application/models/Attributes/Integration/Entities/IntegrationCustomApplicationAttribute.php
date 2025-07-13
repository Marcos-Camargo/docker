<?php

namespace models\Attributes\Integration\Entities;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

class IntegrationCustomApplicationAttribute extends Entity
{
    public $id;
    public $company_id;
    public $store_id;
    public $integration_id;
    public $custom_application_attribute_id;
    public $integration_external_attribute_id;
    public $integration_external_attribute_code;
    public $integration_external_attribute_value;
    public $required;
    public $module;
    public $field_type;
    public $is_variation_attribute;
}