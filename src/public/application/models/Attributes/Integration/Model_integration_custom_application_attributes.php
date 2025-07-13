<?php

namespace models\Attributes\Integration;

use models\Attributes\Integration\Entities\IntegrationCustomApplicationAttribute;
use models\Core\Model\Model;

require_once APPPATH . "models/Core/Model/Model.php";
require_once APPPATH . "models/Attributes/Integration/Entities/IntegrationCustomApplicationAttribute.php";

class Model_integration_custom_application_attributes extends Model
{
    protected $table = 'integration_custom_application_attributes';

    public function __construct()
    {
        parent::__construct(new IntegrationCustomApplicationAttribute());
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}