<?php

class FakeQuotesDbHandler extends FakeDbHandler
{
    private $row;
    public function __construct(array $row = [])
    {
        $this->row = $row;
    }

    public function query($sql, $bindings = [])
    {
        $this->queries[] = ['sql' => $sql, 'bindings' => $bindings];
        $row = $this->row;
        return new class($row) {
            private $row;
            public function __construct($row)
            {
                $this->row = $row;
            }
            public function num_rows()
            {
                return empty($this->row) ? 0 : 1;
            }
            public function row_array()
            {
                return $this->row;
            }
        };
    }
}
