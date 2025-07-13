<?php

namespace App\Libraries\Enum;

class StoreSubaccountStatusFilterEnum extends AbstractEnum
{

    const PENDING = 'pending';
    const WITH_PENDENCIES = 'with_pendencies';
    const WITHOUT_PENDENCIES = 'without_pendencies';
    const WITH_ERROR = 'with_error';

    public static function generateList(): array
    {
        return [
            self::PENDING => lang('application_gateway_subaccount_status_pending'),
            self::WITH_PENDENCIES => lang('application_gateway_subaccount_status_with_pendencies'),
            self::WITHOUT_PENDENCIES => lang('application_gateway_subaccount_status_without_pendencies'),
            self::WITH_ERROR => lang('application_gateway_subaccount_status_without_error'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}