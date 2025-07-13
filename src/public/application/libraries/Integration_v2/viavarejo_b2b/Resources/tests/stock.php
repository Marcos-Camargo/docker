<?php

require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLWrapper.php';
require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/StockDeserializer.php';

try {
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    $xmlObject = simplexml_load_file(
        '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/tests/files/B2BEstoque.xml',
        SimpleXMLWrapper::class
    );
    echo "<pre>";
    $stockDeserializer = new \Integration_v2\viavarejo_b2b\Resources\Mappers\XML\StockDeserializer();
    $stockDeserializer->deserialize($xmlObject);
    echo "IdLojista: {$stockDeserializer->getFlagIdAttributeValue()}\n";
    print_r($stockDeserializer->getDeserializedObject());
} catch (Throwable $e) {
    echo "Error: {$e->getMessage()}";
}