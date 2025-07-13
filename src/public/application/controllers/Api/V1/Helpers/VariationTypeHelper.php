<?php

use libraries\Attributes\Custom\CustomAttributeMapService;
use libraries\Attributes\Application\Resources\CustomAttribute;

require_once APPPATH . "libraries/Attributes/Custom/CustomAttributeMapService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

trait VariationTypeHelper
{

    /**
     * @var CustomAttributeMapService
     */
    protected $customAttributeMapService;

    public function __construct()
    {
        $this->customAttributeMapService = new CustomAttributeMapService();
    }

    protected function fetchCustomAttributesMapByCriteria($value, $variation = [], $codeValue = false)
    {
        if ($codeValue) {
            if (in_array($value, $this->type_variation)) return [$value, $variation];
        }
        if (array_key_exists($value, $this->type_variation)) return [$value, $variation];
        $customAttrMaps = $this->customAttributeMapService->fetchCustomAttributesMapByCriteria([
            'store_id' => $this->store_id,
            'company_id' => $this->company_id,
            'module' => CustomAttribute::PRODUCT_VARIATION_MODULE,
            'status' => CustomAttribute::STATUS_ENABLED,
            'enabled' => CustomAttribute::STATUS_ENABLED,
            'value' => trim($value)
        ]);
        if (!empty($customAttrMaps)) {
            $customAttrMap = current($customAttrMaps) ?? [];
            $value = $codeValue ? ($customAttrMap['code'] ?? $value) : ($customAttrMap['name'] ?? $value);
            $variation[$customAttrMap['code'] ?? $value] = $variation[$customAttrMap['value']] ?? $variation[$customAttrMap['code']] ?? '';
            unset($variation[$customAttrMap['value']]);
        }
        return [$value, $variation];
    }

    protected function sortVariationTypesByCode(array $variationTypesList): array
    {
        return $this->sortVariationTypes($variationTypesList, ['size', 'color', 'voltage', 'flavor','degree','side']);
    }

    protected function sortVariationTypesByName(array $variationTypesList): array
    {
        return $this->sortVariationTypes($variationTypesList, ['tamanho', 'cor', 'voltagem', 'sabor','grau','lado']);
    }

    protected function sortVariationTypes(array $variationTypesList, array $priority): array
    {
        usort($variationTypesList, function ($a, $b) use ($priority) {
            $a = array_search(strtolower($a), $priority);
            $b = array_search(strtolower($b), $priority);
            if ($a === false && $b === false) return 0;
            else if ($a === false) return 1;
            else if ($b === false) return -1;
            else return $a - $b;
        });
        return $variationTypesList;
    }
}