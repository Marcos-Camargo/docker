<?php

namespace libraries\Attributes\Application\Integration;

require_once APPPATH . "libraries/Attributes/Application/Integration/BaseIntegrationAttributeService.php";

class VariationIntegrationAttributeService extends BaseIntegrationAttributeService
{
    // the field in the system has changed to text format, it is no longer necessary to ignore it
    //protected $integrationAttrIgnored = ['voltage', 'voltagem'];
    protected $integrationAttrIgnored = [];


    public function mapSearchCriteria(array $criteria = []): array
    {
        return array_merge($this->buildCriteriaEnabledAttributes($criteria), ['variacao' => 1]);
    }
}