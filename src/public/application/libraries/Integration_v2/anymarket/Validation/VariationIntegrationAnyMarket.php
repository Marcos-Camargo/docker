<?php

require_once APPPATH . "libraries/Attributes/Integration/IntegrationCustomAttributeApplicationService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";

use libraries\Attributes\Integration\IntegrationCustomAttributeApplicationService;
use libraries\Attributes\Custom\CustomApplicationAttributeService;
use \models\Core\Entity\Entity;

/**
 * Class VariationIntegrationAnyMarket
 * @property \libraries\Attributes\Custom\CustomApplicationAttributeService
 * @property \libraries\Attributes\Integration\IntegrationCustomAttributeApplicationService
 */
class VariationIntegrationAnyMarket
{

    private $integrationCustomAttrService;
    private $customAppAttrService;

    private $integrationData;

    public function __construct($integrationData = [])
    {
        $this->integrationCustomAttrService = new IntegrationCustomAttributeApplicationService();
        $this->customAppAttrService = new CustomApplicationAttributeService();
        $this->integrationData = $integrationData;
    }

    public function setIntegrationData($integrationData): VariationIntegrationAnyMarket
    {
        $this->integrationData = $integrationData;
        return $this;
    }

    public function overwriteVariationWithIntegrationCustomAttribute($variation)
    {
        $integrationAttributeValues = $this->integrationCustomAttrService->getAllIntegrationAttributeValueByExternalId([
                'external_id' => $variation->id,
            ] + $this->integrationData);
        foreach ($integrationAttributeValues as $integrationAttributeValue) {
            if ($integrationAttributeValue->exists()) {
                $customAttribute = $this->customAppAttrService->getCustomAttributeById([
                    'company_id' => $integrationAttributeValue->getValueByColumn('company_id'),
                    'store_id' => $integrationAttributeValue->getValueByColumn('store_id'),
                    'id' => $integrationAttributeValue->getValueByColumn('custom_application_attribute_id')
                ], ['id' => $integrationAttributeValue->getValueByColumn('custom_application_attribute_value_id')]);
                if ($customAttribute->exists()) {
                    $filteredAttrValues = array_filter($customAttribute->{'values'} ?? [],
                        function (Entity $value) use ($integrationAttributeValue) {
                            return $integrationAttributeValue->getValueByColumn('custom_application_attribute_value_id') == $value->getValueByColumn('id');
                        });
                    $filteredAttrValue = !empty($filteredAttrValues) ? current($filteredAttrValues) : new Entity();
                    if ($filteredAttrValue->exists()) {
                        $codeSlug = $filteredAttrValue->getValueByColumn('code') ?? '';
                        $description = $filteredAttrValue->getValueByColumn('value') ?? $variation->description;
                        if (strcasecmp($codeSlug, 'default') === 0) {
                            $description = $variation->description;
                        }
                        $variation->description = $description;
                    }
                    $variation->type->name = $customAttribute->getValueByColumn('name') ?? $variation->type->name;
                }
            }
        }
        return $variation;
    }

}