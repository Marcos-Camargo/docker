<?php

namespace App\Libraries\Enum;

class LegalPanelNotificationType extends AbstractEnum
{

    const ORDER = 'order';
    const OTHERS = 'others';

    public static function generateList(): array
    {
        return [
            self::ORDER => lang('application_legal_panel_notification_type_order'),
            self::OTHERS => lang('application_legal_panel_notification_type_others'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}