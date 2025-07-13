<?php

namespace App\Libraries\Enum;

class CampaignSegment extends AbstractEnum
{

    const CATEGORY = 'category';
    const STORE = 'store';
    const PRODUCT = 'product';

    public static function generateList(): array
    {
        return [
            self::CATEGORY => lang('application_campaign_segment_category'),
            self::STORE => lang('application_campaign_segment_seller'),
            self::PRODUCT => lang('application_campaign_segment_product'),
        ];

    }

}