<?php

namespace libraries\Helpers;

class ObjectSerializer
{
    public static function objectToArray(object $object): array
    {
        $array = [];
        foreach ($object as $key => $value) {
            $array[$key] = is_object($value) ? self::objectToArray($value) : $value;
        }
        return $array;
    }

    public static function arrayToObject(array $array): object
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->{$key} = is_array($value) ? self::arrayToObject($value) : $value;
        }
        return $object;
    }
}