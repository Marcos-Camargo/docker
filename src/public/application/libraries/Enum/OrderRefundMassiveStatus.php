<?php

namespace App\Libraries\Enum;

class OrderRefundMassiveStatus extends AbstractEnum
{
    const READY = 'ready';
    const PROCESSING = 'processing';
    const ERROR = 'error';
    const SUCCESS = 'success';

    public static function generateList(): array
    {
        return [
            self::READY => 'Pronto para Importar',
            self::PROCESSING => 'Processando',
            self::ERROR => 'Erro',
            self::SUCCESS => 'Sucesso',
        ];

    }

    public static function generateListColor(): array
    {
        return [
            self::READY => 'label-info',
            self::PROCESSING => 'label-warning',
            self::ERROR => 'label-danger',
            self::SUCCESS => 'label-success',
        ];

    }

    public static function getName(string $code): string
    {
        return self::generateList()[$code] ?? '';
    }

    public static function getColor(string $code): string
    {
        return self::generateListColor()[$code] ?? '';
    }

}