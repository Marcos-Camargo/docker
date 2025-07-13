<?php

namespace libraries\Attributes\Application\Integration\Mappers;

abstract class BaseIntegrationAttributeMapper
{
    public abstract function mapperIntegrationAttributeValues(array $values): array;

    public abstract function mapIntegrationFieldType(string $fieldType): string;
}