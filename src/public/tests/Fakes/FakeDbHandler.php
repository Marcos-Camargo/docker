<?php

class FakeDbHandler
{
    public array $queries = [];
    public array $data = [];

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
