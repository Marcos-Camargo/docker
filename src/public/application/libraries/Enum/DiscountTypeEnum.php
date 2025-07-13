<?php

namespace App\Libraries\Enum;

class DiscountTypeEnum extends AbstractEnum
{

    const PERCENTUAL = 'discount_percentage';
    const FIXED_DISCOUNT = 'fixed_discount';

    public static function generateList(): array
    {

        return [
            self::PERCENTUAL => lang('application_'.self::PERCENTUAL),
            self::FIXED_DISCOUNT => lang('application_'.self::FIXED_DISCOUNT),
        ];

    }

}