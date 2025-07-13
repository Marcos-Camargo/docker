<?php

namespace models\Attributes\Custom;

use models\Attributes\Custom\Entities\CustomApplicationAttributeValue;
use models\Core\Model\Model;

require_once APPPATH . "models/Core/Model/Model.php";
require_once APPPATH . "models/Attributes/Custom/Entities/CustomApplicationAttributeValue.php";

class Model_custom_application_attribute_values extends Model
{
    protected $table = 'custom_application_attribute_values';

    public function __construct()
    {
        parent::__construct(new CustomApplicationAttributeValue());
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}