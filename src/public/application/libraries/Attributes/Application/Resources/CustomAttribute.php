<?php

namespace libraries\Attributes\Application\Resources;


class CustomAttribute
{
    const PRODUCT_VARIATION_MODULE = 'products_variation';
    const PRODUCT_ATTRIBUTE_MODULE = 'products_variation';
    const PRODUCT_CATEGORY_ATTRIBUTE_MODULE = 'products_category_attribute';

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    const REQUIRED = 1;
    const NOT_REQUIRED = 0;

    const FIELD_TYPE_CUSTOM = 'custom';
    const FIELD_TYPE_STRING = 'string';
    const FIELD_TYPE_NUMBER = 'number';
    const FIELD_TYPE_DECIMAL = 'decimal';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_DATETIME = 'datetime';
    const FIELD_TYPE_SELECTABLE = 'selectable';

}