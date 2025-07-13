<?php

namespace libraries\Attributes\Application\Integration;

require_once APPPATH . "libraries/Attributes/Application/Integration/BaseIntegrationAttributeService.php";

class CategoryIntegrationAttributeService extends BaseIntegrationAttributeService
{

    public function mapSearchCriteria(array $criteria = []): array
    {
        return [
            'id_categoria' => $criteria['category_marketplace_id'],
            'variacao' => 0
        ];
    }
}