<?php

namespace App\Libraries\Enum;

class CampaignStatus extends AbstractEnum
{

    const SCHEDULE = 'schedule';
    const AVAILABLE = 'available';
    const EXPIRED = 'expired';
    const ADHERED = 'adhered';
    const INACTIVE = 'inactive';

    public static function generateList(): array
    {
        return [
            self::SCHEDULE => lang('application_scheduled'),
            self::AVAILABLE => lang('application_available'),
            self::EXPIRED => lang('application_expired'),
            self::INACTIVE => lang('application_inactive'),
            self::ADHERED => lang('application_adhered'),
        ];

    }

}