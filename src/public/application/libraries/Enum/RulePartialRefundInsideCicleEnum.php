<?php

namespace App\Libraries\Enum;

class RulePartialRefundInsideCicleEnum extends AbstractEnum
{

    const NO_CHARGE = 'no_charge';
    const REFUND_COMMISSION_RETURNED_PRODUCTS_COMMISSION_TOTAL_SHIPPING = 'refund_commission_returned_products_commission_total_shipping';
    const REFUND_COMMISSION_RETURNED_PRODUCTS_COMMISSION_PARTIAL_SHIPPING = 'refund_commission_returned_products_commission_partial_shipping';

    public static function generateList(): array
    {
        return [
            self::NO_CHARGE => lang('application_no_charge'),
            self::REFUND_COMMISSION_RETURNED_PRODUCTS_COMMISSION_TOTAL_SHIPPING => lang('application_refund_commission_returned_products+commission_total_shipping'),
            self::REFUND_COMMISSION_RETURNED_PRODUCTS_COMMISSION_PARTIAL_SHIPPING => lang('application_refund_commission_returned_products+commission_partial_shipping'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}