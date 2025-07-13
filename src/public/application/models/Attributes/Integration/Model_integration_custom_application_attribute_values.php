<?php

namespace models\Attributes\Integration;

use models\Attributes\Integration\Entities\IntegrationCustomApplicationAttributeValue;
use models\Core\Model\Model;

require_once APPPATH . "models/Core/Model/Model.php";
require_once APPPATH . "models/Attributes/Integration/Entities/IntegrationCustomApplicationAttributeValue.php";

class Model_integration_custom_application_attribute_values extends Model
{
    protected $table = 'integration_custom_application_attribute_values';

    public function __construct()
    {
        parent::__construct(new IntegrationCustomApplicationAttributeValue());
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}