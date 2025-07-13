<?php

if (!defined('ConvertStatusLojaIntegrada')) {
    define('ConvertStatusLojaIntegrada', '');

    trait ConvertStatusLojaIntegrada
    {
        private function convertStatusToLojaIntegrada($paidStatus)
        {

            switch ($paidStatus) {
                case OrderStatusConst::WAITING_PAYMENT:
                    return LojaIntegradaOrdersStatusConst::WAITING_PAYMENT;
                case OrderStatusConst::WAITING_INVOICE:
                    return LojaIntegradaOrdersStatusConst::PAID_OUT;
                case OrderStatusConst::PROCESSING_PAYMENT:
                    return LojaIntegradaOrdersStatusConst::UNDER_ANALYSIS;
                case OrderStatusConst::CANCELLATION_REQUESTED:
                    return LojaIntegradaOrdersStatusConst::CHARGEBACK;
                case OrderStatusConst::DEVOLUTION_IN_TRANSPORT:
                case OrderStatusConst::MISPLACEMENT_IN_TRANSPORT:
                    return LojaIntegradaOrdersStatusConst::PAYMENT_REFUND;
                case OrderStatusConst::CANCELED_BY_SELLER:
                case OrderStatusConst::CANCELED_BEFORE_PAYMENT:
                case OrderStatusConst::CANCELED_AFTER_PAYMENT:
                case OrderStatusConst::CANCEL_AT_CARRIER:
                case OrderStatusConst::CANCEL_AT_MKTPLACE:
                    return LojaIntegradaOrdersStatusConst::CANCELLED;
                case OrderStatusConst::WAITING_SHIPPING:
                case OrderStatusConst::WAITING_TRACKING:
                case OrderStatusConst::PROCESSING_INVOICE:
                case OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING:
                case OrderStatusConst::INVOICED_WAITING_TRACKING:
                case OrderStatusConst::WAITING_SHIPPING_TO_TRACKING:
                case OrderStatusConst::PLP_SEND_TRACKING_MKTPLACE:
                case OrderStatusConst::WITHOUT_FREIGHT_QUOTE:
                case OrderStatusConst::ERROR_FREIGHT_CONTRACTING:
                    return LojaIntegradaOrdersStatusConst::ORDER_PLACED;
                case OrderStatusConst::SHIPPED_IN_TRANSPORT:
                case OrderStatusConst::SHIPPED_IN_TRANSPORT_45:
                case OrderStatusConst::SHIPPED_IN_TRANSPORT_NOTIFY_MKTPLACE:
                    return LojaIntegradaOrdersStatusConst::SHIPPED;
                case OrderStatusConst::DELIVERED:
                case OrderStatusConst::DELIVERED_NOTIFY_MKTPLACE:
                    return LojaIntegradaOrdersStatusConst::DELIVERED;
                case OrderStatusConst::WAITING_WITHDRAWAL:
                    return LojaIntegradaOrdersStatusConst::READY_TO_WITHDRAWAL;
            }

            return LojaIntegradaOrdersStatusConst::UNDER_ANALYSIS;
        }

    }
}

final class LojaIntegradaOrdersStatusConst
{
    const WAITING_PAYMENT = 2;
    const UNDER_ANALYSIS = 3;
    const PAID_OUT = 4;
    const CHARGEBACK = 6;
    const PAYMENT_REFUND = 7;
    const CANCELLED = 8;
    const ORDER_PLACED = 9;
    const SHIPPED = 11;
    const READY_TO_WITHDRAWAL = 13;
    const DELIVERED = 14;
}
