<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

/**
 * Class Model_state
 */
class Model_states extends CI_Model
{
    private $table = 'states';

    public function __construct() {
		parent::__construct();
    }
    
    public function get($uf = null): ?array
    {
        if (!is_null($uf)) {
            return $this->db->get_where($this->table, ['Uf' => $uf])->row_array();
        }

        return $this->db->order_by('Nome')->get($this->table)->result_array();
    }
}