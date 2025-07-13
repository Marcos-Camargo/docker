<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers\XML;

class AvailabilityDeserializer extends BaseObjectDeserializer
{

    const NODE_PRODUCT = 'Produto';
    const NODE_CAMPAIGN = 'Campanha';

    const NODE_PRODUCT_LIST = 'Produtos';
    const NODE_CAMPAIGN_LIST = 'Campanhas';

    protected $nodeCanBeList = [
        self::NODE_PRODUCT,
        self::NODE_CAMPAIGN
    ];

    protected $mapNodeToList = [
        self::NODE_PRODUCT => self::NODE_PRODUCT_LIST,
        self::NODE_CAMPAIGN => self::NODE_CAMPAIGN_LIST
    ];

    protected function limitDeserializationProcessing()
    {
        $this->stopDeserializeRecursion = true;
    }
}