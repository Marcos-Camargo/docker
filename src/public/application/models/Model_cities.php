<?php
/**
 * Class Model_cities
 */
class Model_cities extends CI_Model
{
    private $table = 'cities';

    public function __construct() {
		parent::__construct();
    }
    
    public function getCodeByCityAndUf($city, $uf): ?array
    {
        return $this->db
            ->select("$this->table.code_city, $this->table.code_uf")
            ->join("states s", "s.CodigoUf = $this->table.code_uf")
            ->where(array(
                "$this->table.name" => $city,
                "s.Uf" => $uf
            ))
            ->get($this->table)
            ->row_array();
    }
}