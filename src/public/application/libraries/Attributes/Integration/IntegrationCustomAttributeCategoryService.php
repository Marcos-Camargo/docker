<?php

namespace libraries\Attributes\Integration;

use Integration_v2\anymarket\Providers\IntegrationCategoryProvider;

/**
 * Class IntegrationCustomCategoryAttributeService
 * @package libraries\Attributes\Integration
 * @property IntegrationCategoryProvider $integrationCategoryProvider
 */
class IntegrationCustomAttributeCategoryService
{
    public function __construct(IntegrationCategoryProvider $integrationCategoryProvider)
    {
        $this->integrationCategoryProvider = $integrationCategoryProvider;
    }

    public function fetchIntegrationLinkedCategories(int $integrationId): array
    {
        return $this->integrationCategoryProvider->getLinkedCategoriesByIntegrationId($integrationId);
    }
}