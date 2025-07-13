<?php

namespace App\Libraries\Enum;

class ComissioningType extends AbstractEnum
{
    const BRAND = 'brand';
    const SELLER = 'seller';
    const CATEGORY = 'category';
    const TRADE_POLICY = 'comercial_politics';
    const PRODUCT = 'product';

    const STORE_REGISTER = 'store_register';

    public static function generateList(): array
    {
        return [
            self::BRAND => 'Marca',
            self::SELLER => 'Lojista',
            self::CATEGORY => 'Categoria',
            self::TRADE_POLICY => 'Política Comercial',
            self::PRODUCT => 'SKU',
            self::STORE_REGISTER => 'Cadastro da Loja',
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code] ?? '';
    }

}