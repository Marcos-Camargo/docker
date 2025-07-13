<?php

namespace App\Libraries\Enum;

class PaymentGatewayEnum extends AbstractEnum
{

    const GETNET = 1;
    const PAGARME = 2;
    const PAGSEGURO = 3;
    const MOIP = 4;
    const IUGU = 5;
    const MAGALUPAY = 6;
    const EXTERNO = 7;
    const TUNA = 8;

    public static function generateList(): array
    {

        return [
            self::GETNET => 'Getnet',
            self::PAGARME => 'Pagar.me',
            self::PAGSEGURO => 'Pagseguro',
            self::MOIP => 'Moip',
            self::IUGU => 'Iugu',
            self::MAGALUPAY => 'MagaluPay',
            self::EXTERNO => 'Externo',
            self::TUNA => 'Tuna',
        ];

    }

    public static function generateListValues(): array
    {

        return [
            self::GETNET,
            self::PAGARME,
            self::PAGSEGURO,
            self::MAGALUPAY,
            self::EXTERNO,
            self::TUNA,
            // self::IUGU,
        ];

    }

}