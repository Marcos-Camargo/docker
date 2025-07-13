<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers\XML;

class StockDeserializer extends BaseObjectDeserializer
{

    const NODE_STOCK = 'Estoque';

    const NODE_STOCK_LIST = 'Estoques';

    protected $nodeCanBeList = [
        self::NODE_STOCK
    ];

    protected $mapNodeToList = [
        self::NODE_STOCK => self::NODE_STOCK_LIST,
    ];

}