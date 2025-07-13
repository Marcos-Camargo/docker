<?php

namespace App\Libraries\Enum;

class TransferIntervalEnum extends AbstractEnum
{

    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';

    public static function generateList(): array
    {
        return [
            self::DAILY => lang('application_daily'),
            self::WEEKLY => lang('application_weekly'),
            self::MONTHLY => lang('application_monthly'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}