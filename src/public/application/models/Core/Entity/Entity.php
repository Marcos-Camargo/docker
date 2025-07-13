<?php

namespace models\Core\Entity;

class Entity
{
    private $record = [];
    private $columns = [];

    public function __construct()
    {
        $this->initializeColumnsTable();
    }

    protected function initializeColumnsTable()
    {
        $reflectionObject = (new \ReflectionObject($this));
        $properties = $reflectionObject->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            array_push($this->columns, $property->getName());
        }
    }

    public function assignEntityColumnsValues($data)
    {
        $this->record = [];
        foreach ($this->columns as $column) {
            if (isset($data[$column])) {
                $this->record[$column] = $data[$column];
            }
            $this->setValueByColumn($column, $data[$column] ?? null);
        }
        return $this->record;
    }

    public function assignEntityColumnsValuesToCreate($data)
    {
        return $this->assignEntityColumnsValues($data);
    }

    public function assignEntityColumnsValuesToUpdate($data)
    {
        return $this->assignEntityColumnsValues($data);
    }


    public function setValueByColumn($column, $value)
    {
        if (property_exists($this, $column)) {
            $this->{$column} = $value;
        }
    }

    public function mapColumns(array $raw): array
    {
        $mapped = [];
        foreach ($raw as $col => $value) {
            if (in_array($col, $this->columns)) {
                $mapped[$col] = $value;
            }
        }
        return $mapped;
    }

    public function getValueByColumn($column)
    {
        return property_exists($this, $column) ? $this->{$column} : null;
    }

    public function exists()
    {
        return $this->getValueByColumn(current($this->columns)) ?? false;
    }

}