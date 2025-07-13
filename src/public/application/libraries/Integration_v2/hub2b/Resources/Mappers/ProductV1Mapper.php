<?php

namespace libraries\Integration_v2\hub2b\Resources\Mappers;

class ProductV1Mapper
{

    public static function toV2(object $product): object
    {
        return (object)[
            'id' => $product->id ?? null,
            'sourceId' => $product->sourceId ?? null,
            'destinationId' => $product->destinationId ?? null,
            'name' => $product->name ?? null,
            'ean' => $product->ean13 ?? null,
            'brand' => $product->brand ?? null,
            'ncm' => $product->ncm ?? null,
            'warranty' => $product->warrantyMonths ?? null,
            'idProductType' => strcasecmp($product->productType, 'variation') === 0 ? 3 : (strcasecmp($product->productType, 'simple') === 0 ? 1 : 2),
            'categorization' => self::categorizationToV2($product),
            'description' => self::descriptionToV2($product),
            'images' => self::imagesToV2($product),
            'attributes' => self::attributesToV2($product),
            'sourcePrices' => self::sourcePricesToV2($product),
            'stocks' => self::stocksToV2($product),
            'groupers' => self::groupersToV2($product),
            'skus' => self::skusToV2($product),
            'status' => self::statusToV2($product)
        ];
    }

    public static function categorizationToV2(object $product): object
    {
        return (object)[
            'source' => (object)[
                'name' => $product->category
            ]
        ];
    }

    public static function descriptionToV2(object $product): object
    {
        return (object)[
            'sourceDescription' => $product->description,
            'description' => $product->description
        ];
    }

    public static function dimensionsToV2(object $product): object
    {
        return (object)[
            'height' => $product->height,
            'width' => $product->width,
            'length' => $product->length,
            'weight' => $product->weightKg,
        ];
    }

    public static function imagesToV2(object $product): array
    {
        return array_map(function ($image) {
            return (object)['url' => $image->url ?? ''];
        }, $product->images ?? []);
    }

    public static function attributesToV2(object $product): array
    {
        return array_map(function ($specification) {
            return (object)[
                'name' => $specification->name,
                'value' => $specification->value,
                'attributeType' => $specification->type
            ];
        }, $product->specifications ?? []);
    }

    public static function sourcePricesToV2(object $product): object
    {
        return (object)[
            'priceBase' => $product->priceBase,
            'priceSale' => $product->priceSale,
        ];
    }

    public static function stocksToV2(object $product): object
    {
        return (object)[
            'sourceStock' => $product->stock ?? 0,
            'virtualStock' => 0,
            'handlingTime' => $product->handlingTime ?? 0,
        ];
    }

    public static function groupersToV2(object $product): object
    {
        $parentSKU = !empty($product->parentSKU) ? $product->parentSKU : null;
        return (object)[
            'parentSKU' => $parentSKU,
            'grouper' => $parentSKU,
            'destinationGrouper' => $parentSKU
        ];
    }

    public static function skusToV2(object $product): object
    {
        return (object)[
            'source' => $product->sku,
            'destination' => $product->marketplaceSKU
        ];
    }

    public static function statusToV2(object $product): object
    {
        return (object)[
            'id' => strcasecmp($product->status, 'synchronized') ? 1 : 0,
            'message' => $product->statusMessage ?? ''
        ];
    }
}