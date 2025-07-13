<?php

namespace App\Libraries\Enum;

class AnticipationStatusFilterEnum extends AbstractEnum
{

    const NORMAL = 'normal';
    const IN_ANTICIPATION = 'in_anticipation';
    const APPROVED = 'approved';
    const REFUSED = 'refused';

    public static function generateList(): array
    {
        return [
            self::NORMAL => lang('application_anticipation_normal'),
            self::IN_ANTICIPATION => lang('application_anticipated'),
            self::APPROVED => lang('application_anticipated_approved'),
            self::REFUSED => lang('application_anticipated_refused'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}