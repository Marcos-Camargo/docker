<?php

namespace App\Libraries\Enum;

class RuleFullRefundInsideCicleEnum extends AbstractEnum
{

    const NO_CHARGE = 'no_charge';
    const COMISSION_REVERSAL = 'commission_reversal';

    public static function generateList(): array
    {
        return [
            self::NO_CHARGE => lang('application_no_charge'),
            self::COMISSION_REVERSAL => lang('application_commission_reversal'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}