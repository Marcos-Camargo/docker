<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('moneyToInt')) {
    function moneyToInt(string $value): int
    {
        $value = str_replace(',', '.', $value);
        return (int)number_format((float)$value, 2, '', '');
    }
}

if (!function_exists('intToMoney')) {
    function intToMoney(int $value, $prefix=''): string
    {
        $value/= 100;
        return $prefix.number_format($value, 2, ',', '.');
    }
}

if (!function_exists('intToDecimalDatabase')) {
    function intToDecimalDatabase(int $value): string
    {
        $value/= 100;
        return number_format($value, 2, '.', '');
    }
}

if (!function_exists('roundDecimalsUp')) {
    function roundDecimalsUp($val, int $dec = 2): string
    {
        $pow = pow(10, $dec);
        return ceil($pow * $val) / $pow;
    }
}

if (!function_exists('roundDecimalsDown')) {
    function roundDecimalsDown($val, int $dec = 2): string
    {
        $pow = pow(10, $dec);
        return floor($val * $pow) / $pow;
    }
}

if (!function_exists('money')) {
    function money($val, string $prefix = 'R$'): string
    {
		if(is_numeric($val)){
			return $prefix.' '.number_format($val, 2, ',', '.');
		}else{
			return $prefix.' '.$val;
		}
        
    }
}

if (!function_exists('moneyFloatToVtex')) {
    function moneyFloatToVtex(float $price): int
    {
    	$valor = substr(strpbrk($price, '.,'), 1);   
		$price = (strlen($valor) > 2) ? substr($price, 0, -1) : $price;
		
        // Garante a retirada de todas as casas decimais excendentes
        $price = ($price * 100) / 100;

        // Garantir apenas duas casas decimais
       $price = number_format($price, 2, '.', '');

        // Multiplicar por 100 para deixa o preço sem decimal. 199.98 => 19998
        return round($price * 100);
    }
}

if (!function_exists('moneyVtexToFloat')) {
    function moneyVtexToFloat(int $price): float
    {
        return (float)($price / 100);
    }
}

if (!function_exists('roundDecimal')) {
    function roundDecimal(float $price, int $decimal = 2): float
    {
        return (float)number_format($price, $decimal, '.', '');
    }
}

if (!function_exists('decimalNumber')) {
    function decimalNumber($value)
    {
        if (is_float($value) || is_double($value)) return $value;
        return ($value === null ? null : (float)str_replace(['.', ','], ['', '.'], $value));
    }
}

if (!function_exists('moneyToFloat')) {
    function moneyToFloat(string $price): float
    {
        $price = trim(str_replace('R$', '', $price));
        $price = str_replace('.', '', $price);
        $price = str_replace(',', '.', $price);

        return (float)$price;
    }
}

if (!function_exists('forceNumberToFloat')) {
    function forceNumberToFloat($num): float
    {
        $num = trim($num);

        $replace = strpos($num, ',');

        if ($replace !== false) $num = str_replace(",", ".", $num);

        $validDecimal = substr_count($num, '.');
        if ($validDecimal > 1) {
            $countDecimal = 0;
            $newNum = '';
            for ($i = 0; $i < strlen($num); $i++) {
                if ($num[$i] == '.') {
                    $countDecimal++;
                    if ($countDecimal != $validDecimal) continue;
                }
                $newNum .= $num[$i];
            }
            $num = $newNum;
        }

        return (float)$num;
    }
}
if (!function_exists('isValidDecimal')) {
    function isValidDecimal($string) {
        return preg_match('/^\d+\.\d{2}$/', $string);
    }
}


if (!function_exists('removeExtraDots')) {
    function removeExtraDots($string)
    {
        // Encontra a posição do último ponto
        $lastDotPos = strrpos($string, '.');

        // Se não houver pontos ou houver apenas um ponto, retorna a string original
        if ($lastDotPos === false) {
            return $string;
        }

        // Separa a string em duas partes: antes e depois do último ponto
        $beforeLastDot = substr($string, 0, $lastDotPos);
        $afterLastDot = substr($string, $lastDotPos);

        // Remove todos os pontos da parte antes do último ponto
        $beforeLastDot = str_replace('.', '', $beforeLastDot);

        // Concatena a parte antes do último ponto (sem pontos) com a parte depois do último ponto
        return $beforeLastDot . $afterLastDot;
    }
}
