<?php

namespace Logistic\Repositories\CI\v1;

/**
 * Class BaseCIRepository
 * @package Logistic\Repositories\CI\v1
 * @property \CI_Loader $load
 */
class BaseCIRepository
{
    protected $fillable = [];

    private static $_ci_instance;

    public function __get($name)
    {
        return (self::getInstance())->{$name};
    }

    public static function getInstance(): \CI_Controller
    {
        if (self::$_ci_instance === null || !isset(self::$_ci_instance)) {
            self::$_ci_instance =& get_instance();
        }
        return self::$_ci_instance;
    }

    protected function handleFillableFields(array $data): array
    {
        if (empty($this->fillable)) return $data;

        $mapped = [];
        foreach ($this->fillable as $field => $mappedFields) {
            if (!empty($mappedFields)) {
                foreach ($mappedFields as $mappedField) {
                    if (array_key_exists($mappedField, $data)) {
                        $mapped[$field] = $data[$mappedField];
                    }
                }
            }
            if (array_key_exists($field, $data)) {
                $mapped[$field] = $data[$field];
            }
        }
        return $mapped;
    }
}