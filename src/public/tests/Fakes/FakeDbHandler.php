<?php

class FakeDbHandler
{
    public array $queries = [];
    public array $data = [];

    public function select($fields)
    {
        $this->queries[] = ['sql' => 'select ' . $fields];
        return $this;
    }

    public function from($table)
    {
        $this->queries[] = ['sql' => 'from ' . $table];
        return $this;
    }

    public function join($table, $condition)
    {
        $this->queries[] = ['sql' => 'join ' . $table . ' on ' . $condition];
        return $this;
    }

    public function where_in($field, $values)
    {
        $this->queries[] = ['sql' => 'where_in(' . $field . ')'];
        return $this;
    }

    public function get()
    {
        $this->queries[] = ['sql' => 'get'];
        return new class {
            public function result_array()
            {
                return [['mocked' => 'result']];
            }
        };
    }

    public function where($key, $value = null, $escape = null)
    {
        return $this;
    }

    public function query($sql, $bindings = [])
    {
        $this->queries[] = ['sql' => $sql, 'bindings' => $bindings];
        return new class {
            public function result_array()
            {
                return [['mocked' => 'result']];
            }
        };
    }

    public function get_where($table, $where)
    {
        return new class {
            public function row_array()
            {
                return ['mocked' => 'data'];
            }
        };
    }

    public function delete($table)
    {
        return true;
    }

    public function insert($table, $data)
    {
        return true;
    }
}
