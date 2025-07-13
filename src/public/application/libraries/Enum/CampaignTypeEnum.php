<?php

namespace App\Libraries\Enum;

class CampaignTypeEnum extends AbstractEnum
{

    const SHARED_DISCOUNT = 'shared_discount';
    const CHANNEL_FUNDED_DISCOUNT = 'channel_funded_discount';
    const MERCHANT_DISCOUNT = 'merchant_discount';
    const COMMISSION_REDUCTION_AND_REBATE = 'commission_reduction_and_rebate';
    const MARKETPLACE_TRADING = 'marketplace_trading';

    public static function generateList(array $blacklist = []): array
    {
        $discounts = [
            self::SHARED_DISCOUNT => lang('application_'.self::SHARED_DISCOUNT),
            self::CHANNEL_FUNDED_DISCOUNT => lang('application_'.self::CHANNEL_FUNDED_DISCOUNT),
            self::MERCHANT_DISCOUNT => lang('application_'.self::MERCHANT_DISCOUNT),
            self::COMMISSION_REDUCTION_AND_REBATE => lang('application_'.self::COMMISSION_REDUCTION_AND_REBATE),
            self::MARKETPLACE_TRADING => lang('application_'.self::MARKETPLACE_TRADING),
        ];

        return array_filter($discounts, function ($key) use ($blacklist) {
            return !in_array($key, $blacklist);
        }, ARRAY_FILTER_USE_KEY);
    }

}