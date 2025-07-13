<?php

require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLWrapper.php';
require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/ProductCatalogDeserializer.php';

try {
    $xmlObject = simplexml_load_file(
        '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/tests/files/B2BParcial_2294.xml',
        SimpleXMLWrapper::class
    );
    echo "<pre>";
    $prodDeserializer = new \Integration_v2\viavarejo_b2b\Resources\Mappers\XML\ProductCatalogDeserializer();
    $prodDeserializer->deserialize($xmlObject);
    $prodDeserializer->getDeserializedObject();
    echo "IdLojista: {$prodDeserializer->getFlagIdAttributeValue()}\n";
    print_r($prodDeserializer->getDeserializedObject());
} catch (Throwable $e) {
    echo "Error: {$e->getMessage()}";
}