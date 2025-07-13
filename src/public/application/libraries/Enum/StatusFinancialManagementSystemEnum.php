<?php

namespace App\Libraries\Enum;

class StatusFinancialManagementSystemEnum extends AbstractEnum
{

    const CREATING = 'creating';
    const ERROR = 'error';
    const CREATED = 'created';
    const PENDING = 'pending';
    const UPDATING = 'updating';

    public static function generateList(): array
    {
        return [
            self::CREATING => lang('messages_creating'),
            self::ERROR => lang('messages_error_occurred'),
            self::CREATED => lang('application_created'),
            self::PENDING => lang('messages_pending'),
            self::UPDATING => lang('messages_updating'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}