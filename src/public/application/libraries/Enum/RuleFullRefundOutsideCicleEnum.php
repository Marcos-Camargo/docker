<?php

namespace App\Libraries\Enum;

class RuleFullRefundOutsideCicleEnum extends AbstractEnum
{

    const REFUND_GROSS_ORDER_AMOUNT = 'refund_gross_order_amount';
    const REVERSAL_NET_ORDER_VALUE = 'reversal_net_order_value';

    public static function generateList(): array
    {
        return [
            self::REFUND_GROSS_ORDER_AMOUNT => lang('application_refund_gross_order_amount'),
            self::REVERSAL_NET_ORDER_VALUE => lang('application_reversal_net_order_value'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}