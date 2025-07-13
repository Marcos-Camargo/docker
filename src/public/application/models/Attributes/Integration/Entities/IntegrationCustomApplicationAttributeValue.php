<?php

namespace models\Attributes\Integration\Entities;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

class IntegrationCustomApplicationAttributeValue extends Entity
{
    public $id;
    public $company_id;
    public $store_id;
    public $integration_id;
    public $integration_custom_application_attribute_id;
    public $custom_application_attribute_id;
    public $custom_application_attribute_value_id;
    public $integration_external_attribute_value_id;
    public $integration_external_attribute_value_code;
    public $integration_external_attribute_value_value;
}