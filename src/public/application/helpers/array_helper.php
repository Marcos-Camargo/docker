<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('array_partial_search')) {
    function array_partial_search($array, $keyword) : int
    {
        // Loop through each item and check for a match.
        foreach ($array as $string => $maxSize) {
            // If found somewhere inside the string, add.
            if (strpos($keyword, $string) !== false) {
                return $maxSize;
            }
        }
        return 0;
    }
}


if (!function_exists('array_msort')) {
    function array_msort($array, $cols): array
    {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_' . $k] = strtolower($row[$col]);
            }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
        }
        $eval = substr($eval, 0, -1) . ');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;
    }
}

if (!function_exists('getArrayByValueIn')) {
    function getArrayByValueIn(?array $array, $fieldValidate, $fieldArray, bool $return_index = false)
    {
        if ($array === null) {
            return array();
        }

        $array_filter = array_filter($array, function($item) use ($fieldValidate, $fieldArray) {
            if (is_array($fieldValidate)) {
                $all_true = true;
                foreach ($fieldValidate as $key => $field) {
                    if (($item->$fieldArray[$key] ?? $item[$fieldArray[$key]]) != $field) {
                        $all_true = false;
                    }
                }
                return $all_true;
            }
            return ($item->$fieldArray ?? $item[$fieldArray]) == $fieldValidate;
        });

        if ($return_index) {
            return key($array_filter);
        }

        return current($array_filter);
    }
}

if (!function_exists('getMaxValueInArray')) {
    function getMaxValueInArray(?array $array, string $fieldValidate): int
    {
        if ($array === null) {
            return 0;
        }

        return array_reduce($array, function($carry, $item) use($fieldValidate) {
            if (($item[$fieldValidate] ?? $item->$fieldValidate) > $carry){
                $carry = $item[$fieldValidate] ?? $item->$fieldValidate;
            }
            return $carry;
        });
    }
}