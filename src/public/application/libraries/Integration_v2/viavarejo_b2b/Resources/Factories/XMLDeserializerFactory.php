<?php

namespace Integration_v2\viavarejo_b2b\Resources\Factories;

use Integration_v2\viavarejo_b2b\Resources\Mappers\FileNameMapper;
use Integration_v2\viavarejo_b2b\Resources\Mappers\XML\BaseObjectDeserializer;

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FileNameMapper.php';

class XMLDeserializerFactory
{

    public static function provideDeserializerByFilePath(string $filePath): BaseObjectDeserializer
    {
        return XMLDeserializerFactory::providerFactory(
            FileNameMapper::mapFileDeserializer($filePath)
        );
    }

    public static function providerFactory($className): BaseObjectDeserializer
    {
        try {
            require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/{$className}.php";
            $instance = "Integration_v2\\viavarejo_b2b\\Resources\\Mappers\\XML\\{$className}";
            return new $instance();
        } catch (\Throwable $e) {
            return new BaseObjectDeserializer();
        }
    }

}