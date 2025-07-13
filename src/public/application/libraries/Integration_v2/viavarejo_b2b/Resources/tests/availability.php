<?php

require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLWrapper.php';
require_once '/var/www/html/conectala/application/libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/AvailabilityDeserializer.php';

try {
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    $xmlObject = simplexml_load_file(
        '/var/www/html/conectala/application/libraries/Integration_v2/viavarejo_b2b/Resources/tests/files/B2BDisponibilidade_1526.xml',
        SimpleXMLWrapper::class
    );
    echo "<pre>";
    $result = (new \Integration_v2\viavarejo_b2b\Resources\Mappers\XML\AvailabilityDeserializer())->deserialize($xmlObject);
    print_r($result);
} catch (Throwable $e) {
    echo "Error: {$e->getMessage()}";
}