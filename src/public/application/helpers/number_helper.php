<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('onlyNumbers')) {
    function onlyNumbers(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return preg_replace('/\D/', '', $value);
    }
}

if (!function_exists('mask')) {
    function mask($val, $mask): string
    {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k])) $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i])) $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }
}

if (!function_exists('cpf')) {
    function cpf(string $val): string
    {
        $val = onlyNumbers($val);
        return mask($val, '###.###.###-##');
    }
}

if (!function_exists('cnpj')) {
    function cnpj(string $val): string
    {
        $val = onlyNumbers($val);
        return mask($val, '##.###.###/####-##');
    }
}

if (!function_exists('clearCPFCNPJ')) {
    function clearCPFCNPJ($valor){
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);
        return $valor;
    }
}

if (!function_exists('separatePhoneAndDdd')) {
    function separatePhoneAndDdd(?string $phone, string $ddd_default = '00'): ?array
    {
        if (empty($phone)) {
            return null;
        }

        // Telefone não tem DDD.
        if (strlen($phone) <= 9) {
            return [
                'ddd'   => $ddd_default,
                'phone' => $phone
            ];
        }

        return [
            'ddd'   => substr($phone, 0, 2),
            'phone' => substr($phone, 2)
        ];
    }
}

if (!function_exists('extractNumberBeforeSpace')) {
    function extractNumberBeforeSpace($string)
    {
        if (preg_match('/^\d+/', $string, $matches)) {
            return $matches[0];
        }
        return null;
    }
}