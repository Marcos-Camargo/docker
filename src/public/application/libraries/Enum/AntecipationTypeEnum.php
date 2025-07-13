<?php

namespace App\Libraries\Enum;

class AntecipationTypeEnum extends AbstractEnum
{

    const DX = '1025';
    const FULL = 'Full';

    public static function generateList(): array
    {
        return [
            self::DX => 'D+X',
            self::FULL => 'Full',
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}