<?php

namespace App\Libraries\Enum;

class ComissionRuleEnum extends AbstractEnum
{

    const NEW_COMISSION = 'new_comission';
    const COMISSION_REBATE = 'comission_rebate';

    public static function generateList(): array
    {

        return [
            self::NEW_COMISSION => lang('application_'.self::NEW_COMISSION),
            self::COMISSION_REBATE => lang('application_'.self::COMISSION_REBATE),
        ];

    }

}