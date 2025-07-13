<?php

namespace App\Libraries\Enum;

class RulePartialRefundOutsideCicleEnum extends AbstractEnum
{

    const REFUND_GROSS_VALUE_RETURNED_PRODUCTS_PRODUCT_TOTAL_FREIGHT = 'refund_gross_value_returned_products_product_total_freight';
    const REFUND_GROSS_VALUE_RETURNED_PRODUCTS_PRODUCT_PARTIAL_FREIGHT = 'refund_gross_value_returned_products_product_partial_freight';
    const REFUND_NET_VALUE_RETURNED_PRODUCTS_PRODUCTS_TOTAL_SHIPPING_PRODUCT_COMMISSION_TOTAL_SHIPPING_COMMISSION = 'refund_net_value_returned_products_products_total_shipping_product_commission_total_shipping_commission';
    const REFUND_NET_VALUE_RETURNED_PRODUCTS_PRODUCTS_PARTIAL_SHIPPING_PRODUCT_COMMISSION_PARTIAL_SHIPPING_COMMISSION = 'refund_net_value_returned_products_products_partial_shipping_product_commission_partial_shipping_commission';

    public static function generateList(): array
    {
        return [
            self::REFUND_GROSS_VALUE_RETURNED_PRODUCTS_PRODUCT_TOTAL_FREIGHT => lang('application_refund_gross_value_returned_products_product_total_freight'),
            self::REFUND_GROSS_VALUE_RETURNED_PRODUCTS_PRODUCT_PARTIAL_FREIGHT => lang('application_refund_gross_value_returned_products_product_partial_freight'),
            self::REFUND_NET_VALUE_RETURNED_PRODUCTS_PRODUCTS_TOTAL_SHIPPING_PRODUCT_COMMISSION_TOTAL_SHIPPING_COMMISSION => lang('application_refund_net_value_returned_products_products_total_shipping_product_commission_total_shipping_commission'),
            self::REFUND_NET_VALUE_RETURNED_PRODUCTS_PRODUCTS_PARTIAL_SHIPPING_PRODUCT_COMMISSION_PARTIAL_SHIPPING_COMMISSION => lang('application_refund_net_value_returned_products_products_partial_shipping_product_commission_partial_shipping_commission'),
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code];
    }

}