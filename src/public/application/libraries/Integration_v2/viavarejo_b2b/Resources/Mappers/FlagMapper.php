<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers;

class FlagMapper
{
    const INTEGRATION = 'viavarejo_b2b';
    const INTEGRATION_ERP = 'via';

    const FLAG_CASASBAHIA = 'casasbahia';
    const FLAG_EXTRA = 'extra';
    const FLAG_PONTOFRIO = 'pontofrio';

    const FLAG_CASASBAHIA_ID = 10037;
    const FLAG_EXTRA_ID = 15;
    const FLAG_PONTOFRIO_ID = 16;

    const MAP_FLAG_ID = [
        self::FLAG_CASASBAHIA_ID => self::FLAG_CASASBAHIA,
        self::FLAG_EXTRA_ID => self::FLAG_EXTRA,
        self::FLAG_PONTOFRIO_ID => self::FLAG_PONTOFRIO,
    ];

    const MAP_FLAG_NAME = [
        self::FLAG_CASASBAHIA => self::FLAG_CASASBAHIA_ID,
        self::FLAG_EXTRA => self::FLAG_EXTRA_ID,
        self::FLAG_PONTOFRIO => self::FLAG_PONTOFRIO_ID,
    ];

    const MAP_IMAGES_HOSTS = [
        self::FLAG_CASASBAHIA => 'https://www.casasbahia-imagens.com.br',
        self::FLAG_EXTRA => 'https://www.extra-imagens.com.br',
        self::FLAG_PONTOFRIO => 'https://www.pontofrio-imagens.com.br',
    ];

    const ENABLED_FLAGS = [
        self::FLAG_CASASBAHIA => 'Casas Bahia',
        self::FLAG_EXTRA => 'Extra',
        self::FLAG_PONTOFRIO => 'Ponto Frio',
    ];

    public static function getFlagIdByName($name): string
    {
        return self::MAP_FLAG_NAME[$name] ?? '';
    }

    public function getFlagById($id): string
    {
        return self::MAP_FLAG_ID[$id] ?? '';
    }

    public function getFlagByPart($part): ?string
    {
        if (strpos($part, self::FLAG_CASASBAHIA) !== false) {
            return self::FLAG_CASASBAHIA;
        } else if (strpos($part, self::FLAG_EXTRA) !== false) {
            return self::FLAG_EXTRA;
        } else if (strpos($part, self::FLAG_PONTOFRIO) !== false) {
            return self::FLAG_PONTOFRIO;
        }
        return null;
    }

    public static function getIntegrationNameFromFlag(string $flag)
    {
        return implode('_', [self::INTEGRATION, $flag]);
    }
}