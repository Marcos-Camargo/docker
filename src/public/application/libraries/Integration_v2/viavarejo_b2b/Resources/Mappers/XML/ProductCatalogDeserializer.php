<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers\XML;

class ProductCatalogDeserializer extends BaseObjectDeserializer
{
    const NODE_PRODUCT = 'Produto';
    const NODE_SKU = 'Sku';
    const NODE_IMAGE = 'Imagem';
    const NODE_GROUP = 'Grupo';
    const NODE_ITEM = 'Item';

    const NODE_PRODUCT_LIST = 'Produtos';
    const NODE_SKU_LIST = 'Skus';
    const NODE_IMAGE_LIST = 'Imagens';
    const NODE_GROUP_LIST = 'Grupos';
    const NODE_ITEM_LIST = 'Itens';

    protected $nodeCanBeList = [
        self::NODE_PRODUCT,
        self::NODE_SKU,
        self::NODE_IMAGE,
        self::NODE_GROUP,
        self::NODE_ITEM,
    ];

    protected $mapNodeToList = [
        self::NODE_PRODUCT => self::NODE_PRODUCT_LIST,
        self::NODE_SKU => self::NODE_SKU_LIST,
        self::NODE_IMAGE => self::NODE_IMAGE_LIST,
        self::NODE_GROUP => self::NODE_GROUP_LIST,
        self::NODE_ITEM => self::NODE_ITEM_LIST,
    ];
}