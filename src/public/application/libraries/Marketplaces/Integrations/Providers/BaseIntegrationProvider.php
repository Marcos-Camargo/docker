<?php

namespace libraries\Marketplaces\Integrations\Providers;

use libraries\Attributes\Application\Integration\Mappers\BaseIntegrationAttributeMapper;

/**
 * Class BaseIntegrationProvider
 * @package libraries\Marketplaces\Integrations\Providers
 * @property \CI_DB_query_builder $db
 */
abstract class BaseIntegrationProvider
{
    protected $integrationAttributeMapper;

    public function __construct(BaseIntegrationAttributeMapper $integrationAttributeMapper)
    {
        $this->integrationAttributeMapper = $integrationAttributeMapper;
    }

    public abstract function getIntegrationCriteria(array $addCriteria = []);

    public function getIntegrationAttributeMapper(): BaseIntegrationAttributeMapper
    {
        return $this->integrationAttributeMapper;
    }

    public function __get($property)
    {
        return get_instance()->{$property};
    }
}