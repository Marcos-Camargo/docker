<?php

namespace models\Attributes\Custom\Entities;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

class CustomApplicationAttributeValue extends Entity
{
    public $id;
    public $custom_application_attribute_id;
    public $company_id;
    public $store_id;
    public $attribute_value_id;
    public $enabled;
    public $visible;
    public $value;
    public $code;
    public $order;
}