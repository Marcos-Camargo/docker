<?php

namespace App\Libraries\Enum;

abstract class AbstractEnum
{

    public static function getDescription($code): string
    {
        $list = static::generateList();
        return $list[$code];
    }

}