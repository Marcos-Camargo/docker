<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers;

class FileNameMapper
{
    const FILE_COMPLETE_PRODUCT = 'B2BCompleto';
    const FILE_PARTIAL_PRODUCT = 'B2BParcial';
    const FILE_AVAILABILITY = 'B2BDisponibilidade';
    const FILE_STOCK = 'B2BEstoque';

    const MAP_FILE_NAME_URL = [
        self::FILE_COMPLETE_PRODUCT => 'Completo',
        self::FILE_PARTIAL_PRODUCT => 'Parcial',
        self::FILE_AVAILABILITY => 'Disponibilidade',
        self::FILE_STOCK => 'Estoque',
    ];

    const MAP_DESERIALIZER_CLASS = [
        self::FILE_COMPLETE_PRODUCT => 'ProductCatalogDeserializer',
        self::FILE_PARTIAL_PRODUCT => 'ProductCatalogDeserializer',
        self::FILE_AVAILABILITY => 'AvailabilityDeserializer',
        self::FILE_STOCK => 'StockDeserializer',
    ];

    const MAP_QUEUE_MODULE = [
        self::FILE_COMPLETE_PRODUCT => 'ProductsImportViaB2BComplete',
        self::FILE_PARTIAL_PRODUCT => 'ProductsImportViaB2BPartial',
        self::FILE_AVAILABILITY => 'ProductsImportViaB2BAvailability',
        self::FILE_STOCK => 'ProductsImportViaB2BStock',
    ];

    const MAP_SCHEDULE_JOB_CLASS = [
        self::FILE_COMPLETE_PRODUCT => 'Integration_v2/Product/viavarejo_b2b/CreateProduct',
        self::FILE_PARTIAL_PRODUCT => 'Integration_v2/Product/viavarejo_b2b/UpdateProduct',
        self::FILE_AVAILABILITY => 'Integration_v2/Product/viavarejo_b2b/UpdateAvailability',
        self::FILE_STOCK => 'Integration_v2/Product/viavarejo_b2b/UpdateStock',
    ];

    const BASE_URL_FILE_DOWNLOAD_DEV = "https://b2b-servico.%s.viavarejo-hlg.com.br/Arquivos/CatalogoB2B/%s?idParceiro=%s";
    const BASE_URL_FILE_DOWNLOAD = "https://b2b.%s.com.br/Arquivos/CatalogoB2B/%s?idParceiro=%s";

    const ENABLED_DOWNLOAD_FILE_TYPES = [
        self::FILE_COMPLETE_PRODUCT => 'Importação de catálogo de produto (Completo)',
        self::FILE_PARTIAL_PRODUCT => 'Atualização de catálogo de produto (Parcial)',
        self::FILE_AVAILABILITY => 'Atualização de disponibilidade (Preço e Status)',
        self::FILE_STOCK => 'Atualização de estoque'
    ];

    public function getFileTypeByPart($part): ?string
    {
        if (strpos($part, self::FILE_COMPLETE_PRODUCT) !== false) {
            return self::FILE_COMPLETE_PRODUCT;
        } else if (strpos($part, self::FILE_PARTIAL_PRODUCT) !== false) {
            return self::FILE_PARTIAL_PRODUCT;
        } else if (strpos($part, self::FILE_AVAILABILITY) !== false) {
            return self::FILE_AVAILABILITY;
        } else if (strpos($part, self::FILE_STOCK) !== false) {
            return self::FILE_STOCK;
        }
        return null;
    }

    public function mapFileType($file)
    {
        $type = strpos($file, self::FILE_COMPLETE_PRODUCT) !== false ? self::MAP_QUEUE_MODULE[self::FILE_COMPLETE_PRODUCT] : $file;
        $type = strpos($type, self::FILE_PARTIAL_PRODUCT) !== false ? self::MAP_QUEUE_MODULE[self::FILE_PARTIAL_PRODUCT] : $type;
        $type = strpos($type, self::FILE_AVAILABILITY) !== false ? self::MAP_QUEUE_MODULE[self::FILE_AVAILABILITY] : $type;
        return strpos($type, self::FILE_STOCK) !== false ? self::MAP_QUEUE_MODULE[self::FILE_STOCK] : $type;
    }

    public static function mapFileDeserializer($file)
    {
        $className = strpos($file, self::FILE_COMPLETE_PRODUCT) !== false ? self::MAP_DESERIALIZER_CLASS[self::FILE_COMPLETE_PRODUCT] : $file;
        $className = strpos($className, self::FILE_PARTIAL_PRODUCT) !== false ? self::MAP_DESERIALIZER_CLASS[self::FILE_PARTIAL_PRODUCT] : $className;
        $className = strpos($className, self::FILE_AVAILABILITY) !== false ? self::MAP_DESERIALIZER_CLASS[self::FILE_AVAILABILITY] : $className;
        return strpos($className, self::FILE_STOCK) !== false ? self::MAP_DESERIALIZER_CLASS[self::FILE_STOCK] : $className;
    }

    public function mapFileScheduleJob($file)
    {
        $className = strpos($file, self::FILE_COMPLETE_PRODUCT) !== false ? self::MAP_SCHEDULE_JOB_CLASS[self::FILE_COMPLETE_PRODUCT] : $file;
        $className = strpos($className, self::FILE_PARTIAL_PRODUCT) !== false ? self::MAP_SCHEDULE_JOB_CLASS[self::FILE_PARTIAL_PRODUCT] : $className;
        $className = strpos($className, self::FILE_AVAILABILITY) !== false ? self::MAP_SCHEDULE_JOB_CLASS[self::FILE_AVAILABILITY] : $className;
        return strpos($className, self::FILE_STOCK) !== false ? self::MAP_SCHEDULE_JOB_CLASS[self::FILE_STOCK] : $className;
    }

    public static function buildFileDownloadUrl($flagName, $fileType, $partnerId)
    {
        $fileType = self::MAP_FILE_NAME_URL[$fileType] ?? '';
        if (in_array(ENVIRONMENT, ['development', 'development_gcp'])) {
            return sprintf(self::BASE_URL_FILE_DOWNLOAD_DEV, $flagName, $fileType, $partnerId);
        }
        return sprintf(self::BASE_URL_FILE_DOWNLOAD, $flagName, $fileType, $partnerId);
    }

}