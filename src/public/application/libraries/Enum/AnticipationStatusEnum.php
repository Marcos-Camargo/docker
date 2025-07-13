<?php

namespace App\Libraries\Enum;

class AnticipationStatusEnum extends AbstractEnum
{

    const BUILDING = 'building';
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REFUSED = 'refused';
    const CANCELED = 'canceled';

    public static function generateList(): array
    {
        return [
            self::BUILDING => lang('application_anticipation_building'),
            self::PENDING => lang('application_anticipation_pending'),
            self::APPROVED => lang('application_anticipation_approved'),
            self::REFUSED => lang('application_anticipation_refused'),
            self::CANCELED => lang('application_anticipation_canceled'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}