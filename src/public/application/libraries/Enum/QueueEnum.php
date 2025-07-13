<?php

namespace App\libraries\Enum;

class QueueEnum extends AbstractEnum
{

    const DEFAULT = 'default';
    const FAILED = 'failed';
    const EMAIL = 'email';

    public static function generateList(): array
    {

        return [
            self::DEFAULT,
            self::FAILED,
            self::EMAIL,
        ];

    }

}