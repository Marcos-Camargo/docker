<?php

namespace App\libraries\Enum;

class QueueDriverEnum extends AbstractEnum
{

    const SYNC = 'sync';
    const REDIS = 'redis';
    const DATABASE = 'database';
    const ORACLE = 'oracle';

    public static function generateList(): array
    {

        return [
            self::SYNC,
            self::REDIS,
            self::DATABASE,
            self::ORACLE,
        ];

    }

}