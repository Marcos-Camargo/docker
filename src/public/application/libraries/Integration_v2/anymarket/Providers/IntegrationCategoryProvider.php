<?php

namespace Integration_v2\anymarket\Providers;

/**
 * Class CategoryIntegration
 * @package Integration_v2\anymarket\Providers
 * @property \Model_categories_anymaket_from_to $categoryRepo
 */
class IntegrationCategoryProvider
{

    public function __construct(\Model_categories_anymaket_from_to $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    public function getLinkedCategoriesByIntegrationId(int $integrationId): array
    {
        return $this->categoryRepo->getLinkedCategoriesByIntegrationId($integrationId);
    }
}