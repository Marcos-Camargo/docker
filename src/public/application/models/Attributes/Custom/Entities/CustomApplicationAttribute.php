<?php

namespace models\Attributes\Custom\Entities;

use models\Core\Entity\Entity;

require_once APPPATH . "models/Core/Entity/Entity.php";

class CustomApplicationAttribute extends Entity
{
    public $id;
    public $company_id;
    public $store_id;
    public $attribute_id;
    public $category_id;
    public $status;
    public $required;
    public $name;
    public $code;
    public $module;
    public $field_type;
}