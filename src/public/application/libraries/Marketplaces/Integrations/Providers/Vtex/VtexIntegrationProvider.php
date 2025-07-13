<?php

namespace libraries\Marketplaces\Integrations\Providers\Vtex;

require_once APPPATH . "libraries/Marketplaces/Integrations/Providers/BaseIntegrationProvider.php";

use libraries\Marketplaces\Integrations\Providers\BaseIntegrationProvider;

class VtexIntegrationProvider extends BaseIntegrationProvider
{

    public function getIntegrationCriteria(array $addCriteria = []): array
    {
        //$jsonSearch[] = json_encode(['environment' => ENVIRONMENT !== 'development' ? 'vtexcommercestable' : 'vtexcommmercebeta']);
        $jsonSearch[] = json_encode(['environment' => 'vtexcommercestable']);
        $jsonSearch[] = json_encode(['environment' => 'myvtex']);
        $jsonSearch[] = json_encode(['environment' => 'prd']);

        $contains = [];
        foreach ($jsonSearch as $search) {
            array_push($contains, $this->db->compile_binds(
                "JSON_CONTAINS(IF(JSON_VALID(int.auth_data), int.auth_data, '{}'), ?)", [$search]
            ));
        }
        $contains = implode(' OR ', $contains);

        return $addCriteria + [
                "({$contains})" => null
            ];
    }
}